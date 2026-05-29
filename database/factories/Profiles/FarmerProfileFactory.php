<?php

namespace Database\Factories\Profiles;

use App\Models\Address\Barangay;
use App\Models\Address\Municipality;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FarmerProfileFactory extends Factory
{
    protected $model = FarmerProfile::class;

    public function definition(): array
    {
        $municipality = Municipality::inRandomOrder()->first()
            ?? Municipality::first();

        $barangay = Barangay::where('municipality_id', $municipality->id)
            ->inRandomOrder()
            ->first();

        $latJitter = fake()->randomFloat(6, -0.03, 0.03);
        $lngJitter = fake()->randomFloat(6, -0.03, 0.03);

        return [
            'user_id' => null,
            'province_id' => $municipality->province_id,
            'municipality_id' => $municipality->id,
            'barangay_id' => $barangay?->id ?? 1,
            'latitude' => round($municipality->latitude + $latJitter, 6),
            'longitude' => round($municipality->longitude + $lngJitter, 6),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (FarmerProfile $profile): void {
            if (! $profile->user_id) {
                $profile->user_id = User::factory()->create()->id;
            }
        })->afterCreating(function (FarmerProfile $profile): void {
            $role = Role::firstOrCreate(['name' => 'farmer']);
            $profile->user->roles()->syncWithoutDetaching($role->id);
        });
    }
}
