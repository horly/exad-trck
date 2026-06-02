<?php

namespace Database\Factories;

use App\Models\Fleet;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Fleet>
 */
class FleetFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement(['Flotte Kinshasa', 'Flotte Logistique', 'Flotte Direction', 'Flotte Livraison']);

        return [
            'subscription_id' => Subscription::factory(),
            'name' => $name,
            'code' => Str::upper(fake()->unique()->bothify('FLT-###')),
            'description' => fake()->sentence(8),
            'status' => 'active',
        ];
    }
}
