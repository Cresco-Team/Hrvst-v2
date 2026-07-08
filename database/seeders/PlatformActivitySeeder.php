<?php

namespace Database\Seeders;

use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Support\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformActivitySeeder extends Seeder
{
    private const int HISTORY_MONTHS = 60;
    private const int CHUNK_SIZE = 500;

    /**
     * Backdates minimal `posts` rows so PlatformActivityService::monthlyActiveCounts()
     * has real per-month farmer/dealer activity to divide by across the full
     * 5-year window that VegetableMonthlyStatsSeeder claims. Without this,
     * every historical month floors to "1 active user" and the forecast's
     * per-capita rescale massively overshoots — that's the bug being fixed.
     *
     * IMPORTANT ORDERING: must run AFTER PostSeeder. PostSeeder attaches
     * post_items to every Post::whereDoesntHave('postItems') row it finds —
     * if this seeder ran first, PostSeeder would sweep up these bare
     * activity-marker posts and attach real items to them, corrupting them.
     */
    public function run(): void
    {
        $farmerUserIds = FarmerProfile::pluck('user_id')->values();
        $dealerUserIds = DealerProfile::pluck('user_id')->values();

        if ($farmerUserIds->isEmpty() && $dealerUserIds->isEmpty()) {
            $this->command->warn('No farmer/dealer profiles found. Seed profiles first.');
            return;
        }

        $rows = [
            ...$this->buildActivityRows($farmerUserIds, 'supply'),
            ...$this->buildActivityRows($dealerUserIds, 'demand'),
        ];

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            DB::table('posts')->insert($chunk);
        }

        $this->command->info(sprintf(
            'Seeded %d activity posts — %d farmers, %d dealers, linear onboarding over %d months.',
            count($rows),
            $farmerUserIds->count(),
            $dealerUserIds->count(),
            self::HISTORY_MONTHS,
        ));
    }

    /**
     * index 0 → cohortMonthsAgo = 59 (joined earliest)
     * index (count-1) → cohortMonthsAgo = 0 (joined this month)
     * Linear spread in between — cumulative active count grows steadily.
     */
    private function buildActivityRows(Collection $userIds, string $type): array
    {
        $count = $userIds->count();

        if ($count === 0) {
            return [];
        }

        $rows = [];

        foreach ($userIds as $index => $userId) {
            $cohortMonthsAgo = self::HISTORY_MONTHS - 1
                - (int) floor($index / $count * self::HISTORY_MONTHS);

            for ($monthsAgo = $cohortMonthsAgo; $monthsAgo >= 0; $monthsAgo--) {
                $date = now()->startOfMonth()->subMonths($monthsAgo);

                $rows[] = [
                    'user_id' => $userId,
                    'type' => $type,
                    'scheduled_date' => $date->toDateString(),
                    'time_slot' => 'morning',
                    'created_at' => $date,
                    'updated_at' => $date,
                    'deleted_at' => null,
                ];
            }
        }

        return $rows;
    }
}
