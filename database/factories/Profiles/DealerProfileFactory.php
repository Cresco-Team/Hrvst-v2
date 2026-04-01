<?php

namespace Database\Factories\Profiles;

use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealerProfileFactory extends Factory
{
    protected $model = DealerProfile::class;

    public function definition(): array
    {
        return [
            // user_id resolved in configure()
            'user_id' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (DealerProfile $profile): void {
            if (! $profile->user_id) {
                $profile->user_id = User::factory()->create()->id;
            }
        })->afterCreating(function (DealerProfile $profile): void {
            $role = Role::firstOrCreate(['name' => 'dealer']);
            $profile->user->roles()->syncWithoutDetaching($role->id);
        });
    }
}
