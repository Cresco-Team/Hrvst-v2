<?php

namespace Database\Seeders;

use App\Enums\PostItemStatus;
use App\Enums\PostStatus;
use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\PriceHistory;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PostSeeder extends Seeder
{
    /** @var array<int> */
    private array $varietyIds = [];

    /**
     * Latest price keyed by variety_id for O(1) flag resolution.
     *
     * @var array<int, object{price_min: string, price_max: string}>
     */
    private array $latestPrices = [];

    /**
     * Variety IDs grouped by vegetable_id.
     *
     * @var array<int, array<int>>
     */
    private array $varietiesByVegetable = [];

    /** @var array<int> */
    private array $vegetableIds = [];

    private const CHUNK_SIZE = 500;

    public function run(): void
    {
        $this->loadLookups();

        if (empty($this->varietyIds)) {
            $this->command->warn('No varieties found. Run ProductSeeder first.');

            return;
        }

        $seededUserIds = Post::pluck('user_id')->unique()->values()->toArray();

        $farmers = FarmerProfile::whereNotIn('user_id', $seededUserIds)->get();
        $dealers = DealerProfile::whereNotIn('user_id', $seededUserIds)->get();

        if ($farmers->isEmpty() && $dealers->isEmpty()) {
            $this->command->info('All profiles already have posts — nothing to seed.');

            return;
        }

        $this->command->info(sprintf(
            'Seeding %d farmers, %d dealers…',
            $farmers->count(),
            $dealers->count(),
        ));

        $this->seedSupplyPosts($farmers);
        $this->seedDemandPosts($dealers);

        $this->command->info(sprintf(
            '✓ Done — %s posts, %s post items',
            number_format(Post::count()),
            number_format(PostItem::count()),
        ));
    }

    // ── Supply ────────────────────────────────────────────────────────────────

    private function seedSupplyPosts(Collection $farmers): void
    {
        $postRows = [];

        foreach ($farmers as $farmer) {
            // Growing: 10–20 per farmer
            for ($i = 0; $i < fake()->numberBetween(10, 20); $i++) {
                $createdAt = fake()->dateTimeBetween('-60 days', '-5 days')->format('Y-m-d H:i:s');

                $postRows[] = [
                    'user_id' => $farmer->user_id,
                    'vegetable_id' => $this->randomVegetableId(),
                    'type' => PostType::Supply->value,
                    'status' => PostStatus::Growing->value,
                    'target_month' => now()->addMonths(fake()->numberBetween(0, 2))->format('Y-m'),
                    'estimated_total_weight' => fake()->randomFloat(2, 200, 3000),
                    'scheduled_date' => null,
                    'time_slot' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'deleted_at' => null,
                ];
            }

            // Harvested: 10–20 per farmer
            for ($i = 0; $i < fake()->numberBetween(10, 20); $i++) {
                $scheduledDate = Carbon::parse(
                    fake()->boolean(55)
                        ? fake()->dateTimeBetween('now', '+45 days')
                        : fake()->dateTimeBetween('-60 days', '-1 day')
                );
                $createdAt = $scheduledDate->copy()->subDays(fake()->numberBetween(3, 20))->toDateTimeString();

                $postRows[] = [
                    'user_id' => $farmer->user_id,
                    'vegetable_id' => $this->randomVegetableId(),
                    'type' => PostType::Supply->value,
                    'status' => PostStatus::Harvested->value,
                    'target_month' => $scheduledDate->copy()->subMonth()->format('Y-m'),
                    'estimated_total_weight' => fake()->randomFloat(2, 100, 2000),
                    'scheduled_date' => $scheduledDate->toDateString(),
                    'time_slot' => fake()->randomElement(PostTimeSlot::cases())->value,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'deleted_at' => null,
                ];
            }
        }

        $this->bulkInsertWithItems($postRows);
    }

    // ── Demand ────────────────────────────────────────────────────────────────

    private function seedDemandPosts(Collection $dealers): void
    {
        $postRows = [];

        foreach ($dealers as $dealer) {
            // 10–20 demand posts per dealer
            for ($i = 0; $i < fake()->numberBetween(10, 20); $i++) {
                $scheduledDate = Carbon::parse(
                    fake()->boolean(60)
                        ? fake()->dateTimeBetween('now', '+30 days')
                        : fake()->dateTimeBetween('-40 days', '-1 day')
                );
                $createdAt = $scheduledDate->copy()->subDays(fake()->numberBetween(1, 14))->toDateTimeString();

                $postRows[] = [
                    'user_id' => $dealer->user_id,
                    'vegetable_id' => $this->randomVegetableId(),
                    'type' => PostType::Demand->value,
                    'status' => PostStatus::Harvested->value,
                    'target_month' => null,
                    'estimated_total_weight' => null,
                    'scheduled_date' => $scheduledDate->toDateString(),
                    'time_slot' => fake()->boolean(70)
                        ? fake()->randomElement(PostTimeSlot::cases())->value
                        : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'deleted_at' => null,
                ];
            }
        }

        $this->bulkInsertWithItems($postRows);
    }

    /**
     * Inserts a batch of post rows then bulk-inserts their items.
     *
     * @param  array<int, array<string, mixed>>  $postRows
     */
    private function bulkInsertWithItems(array $postRows): void
    {
        if (empty($postRows)) {
            return;
        }

        foreach (array_chunk($postRows, self::CHUNK_SIZE) as $chunk) {
            DB::table('posts')->insert($chunk);
        }

        // Fetch posts with no items (the ones we just inserted)
        $posts = Post::whereDoesntHave('postItems')
            ->get(['id', 'vegetable_id', 'type', 'status', 'scheduled_date', 'created_at']);

        $itemRows = [];

        foreach ($posts as $post) {
            if ($post->status === PostStatus::Growing) {
                continue;
            }

            $isPast = $post->scheduled_date && Carbon::parse($post->scheduled_date)->isPast();
            $isSupply = $post->type === PostType::Supply;

            // Supply: 3–8 items | Demand: 2–6 items
            $itemCount = $isSupply
                ? fake()->numberBetween(3, 8)
                : fake()->numberBetween(2, 6);

            foreach ($this->randomVarietyIds($post->vegetable_id, $itemCount) as $varietyId) {
                $itemRows[] = [
                    'post_id' => $post->id,
                    'variety_id' => $varietyId,
                    'quantity_kg' => fake()->randomFloat(2, 20, 1000),
                    'status' => $isSupply
                        ? $this->resolveSupplyStatus($isPast)->value
                        : $this->resolveDemandStatus($isPast)->value,
                    'created_at' => $post->created_at,
                    'updated_at' => $post->created_at,
                    'deleted_at' => null,
                ];
            }
        }

        foreach (array_chunk($itemRows, self::CHUNK_SIZE) as $chunk) {
            DB::table('post_items')->insert($chunk);
        }
    }

    // ── Lookups ───────────────────────────────────────────────────────────────

    private function loadLookups(): void
    {
        $this->varietyIds = Variety::pluck('id')->toArray();
        $this->vegetableIds = Vegetable::pluck('id')->toArray();

        Variety::all(['id', 'vegetable_id'])->each(function (Variety $v): void {
            $this->varietiesByVegetable[$v->vegetable_id][] = $v->id;
        });

        // Latest price per variety — loaded once, reused O(1) per item
        PriceHistory::query()
            ->select('variety_id', 'price_min', 'price_max', 'recorded_at')
            ->orderBy('recorded_at')
            ->get()
            ->each(function (PriceHistory $p): void {
                $this->latestPrices[$p->variety_id] = $p;
            });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function randomVegetableId(): int
    {
        return $this->vegetableIds[array_rand($this->vegetableIds)];
    }

    /**
     * @return array<int>
     */
    private function randomVarietyIds(int $vegetableId, int $count): array
    {
        $pool = $this->varietiesByVegetable[$vegetableId] ?? $this->varietyIds;
        shuffle($pool);

        return array_slice($pool, 0, min($count, count($pool)));
    }

    private function resolveSupplyStatus(bool $isPast): PostItemStatus
    {
        if (! $isPast) {
            return PostItemStatus::Ongoing;
        }

        return fake()->randomElement([
            PostItemStatus::Fulfilled,
            PostItemStatus::Fulfilled,
            PostItemStatus::Unsettled,
        ]);
    }

    private function resolveDemandStatus(bool $isPast): PostItemStatus
    {
        if (! $isPast) {
            return PostItemStatus::Ongoing;
        }

        return fake()->randomElement([
            PostItemStatus::Fulfilled,
            PostItemStatus::Unsettled,
        ]);
    }
}
