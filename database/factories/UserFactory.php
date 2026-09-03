<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->freeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('0000'),
            'phone_number' => '09'.fake()->numerify('#########'),
            'must_change_pin' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function mustChangePin(): static
    {
        return $this->state(fn () => ['must_change_pin' => true]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
