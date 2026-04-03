<?php

namespace Database\Factories\Profiles;

use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FarmerProfileFactory extends Factory
{
    protected $model = FarmerProfile::class;

    public function definition(): array
    {
        return [
            // user_id is resolved in configure() via afterCreating
            'user_id' => null,
            'province_id' => 1,
            'municipality_id' => 1,
            'barangay_id' => 1,
            // Benguet bounding box: lat 16.2–16.7, lng 120.5–120.8
            'latitude' => fake()->randomFloat(6, 16.2, 16.7),
            'longitude' => fake()->randomFloat(6, 120.5, 120.8),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (FarmerProfile $profile): void {
            // Guarantee a user exists before the profile row is inserted
            if (! $profile->user_id) {
                $profile->user_id = User::factory()->create()->id;
            }
        })->afterCreating(function (FarmerProfile $profile): void {
            // Attach 'farmer' role to the linked user
            $role = Role::firstOrCreate(['name' => 'farmer']);
            $profile->user->roles()->syncWithoutDetaching($role->id);
        });
    }
}
