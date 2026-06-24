<?php

namespace Database\Seeders;

use App\Enums\PostItemStatus;
use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Variety;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PostSeeder extends Seeder
{
    private array $varietyIds = [];

    private array $varietiesByVegetable = [];

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
            for ($i = 0; $i < fake()->numberBetween(10, 20); $i++) {
                $scheduledDate = Carbon::parse(
                    fake()->boolean(55)
                        ? fake()->dateTimeBetween('now', '+45 days')
                        : fake()->dateTimeBetween('-60 days', '-1 day')
                );
                $createdAt = $scheduledDate->copy()->subDays(fake()->numberBetween(3, 20))->toDateTimeString();

                $postRows[] = [
                    'user_id' => $farmer->user_id,
                    'type' => PostType::Supply->value,
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
            for ($i = 0; $i < fake()->numberBetween(10, 20); $i++) {
                $scheduledDate = Carbon::parse(
                    fake()->boolean(60)
                        ? fake()->dateTimeBetween('now', '+30 days')
                        : fake()->dateTimeBetween('-40 days', '-1 day')
                );
                $createdAt = $scheduledDate->copy()->subDays(fake()->numberBetween(1, 14))->toDateTimeString();

                $postRows[] = [
                    'user_id' => $dealer->user_id,
                    'type' => PostType::Demand->value,
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

    // ── Shared insert ─────────────────────────────────────────────────────────

    private function bulkInsertWithItems(array $postRows): void
    {
        if (empty($postRows)) {
            return;
        }

        foreach (array_chunk($postRows, self::CHUNK_SIZE) as $chunk) {
            DB::table('posts')->insert($chunk);
        }

        $posts = Post::whereDoesntHave('postItems')
            ->get(['id', 'type', 'scheduled_date', 'created_at']);

        $itemRows = [];

        foreach ($posts as $post) {
            $isPast = Carbon::parse($post->scheduled_date)->isPast();
            $isSupply = $post->type === PostType::Supply;

            // Supply: 3–8 items | Demand: 2–6 items
            $itemCount = $isSupply
                ? fake()->numberBetween(3, 8)
                : fake()->numberBetween(2, 6);

            foreach ($this->randomVarietyIds($itemCount) as $varietyId) {
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

        Variety::all(['id', 'vegetable_id'])->each(function (Variety $v): void {
            $this->varietiesByVegetable[$v->vegetable_id][] = $v->id;
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function randomVarietyIds(int $count): array
    {
        $pool = $this->varietyIds;
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
            PostItemStatus::Expired,
        ]);
    }

    private function resolveDemandStatus(bool $isPast): PostItemStatus
    {
        if (! $isPast) {
            return PostItemStatus::Ongoing;
        }

        return fake()->randomElement([
            PostItemStatus::Fulfilled,
            PostItemStatus::Expired,
        ]);
    }
}
