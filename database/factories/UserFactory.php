<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::User,
            'status' => 'active',
            'disabled_at' => null,
            'permissions' => [],
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_id' => null,
            'role' => UserRole::Superadmin,
        ]);
    }

    public function admin(?Subscription $subscription = null): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_id' => $subscription?->id ?? Subscription::factory(),
            'role' => UserRole::Admin,
        ]);
    }

    public function simpleUser(?Subscription $subscription = null): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_id' => $subscription?->id ?? Subscription::factory(),
            'role' => UserRole::User,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disabled',
            'disabled_at' => now(),
        ]);
    }
}
