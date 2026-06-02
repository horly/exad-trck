<?php

namespace Database\Factories;

use App\Models\Fleet;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fleet_id' => Fleet::factory(),
            'name' => fake()->randomElement(['Toyota Hilux', 'Mercedes Sprinter', 'Isuzu NPR', 'Yamaha NMAX']),
            'registration_number' => fake()->unique()->bothify('EX-####'),
            'brand' => fake()->randomElement(['Toyota', 'Mercedes', 'Isuzu', 'Yamaha', 'Ford']),
            'model' => fake()->randomElement(['Hilux', 'Sprinter', 'NPR', 'NMAX', 'Transit']),
            'color' => fake()->safeColorName(),
            'year' => fake()->numberBetween(2015, 2026),
            'vehicle_type' => fake()->randomElement([
                'passenger_car',
                'suv_4x4',
                'pickup',
                'fourgonnette',
                'camionnette',
                'van',
                'minibus',
                'truck',
                'bus_coach',
                'motorcycle',
                'tricycle',
                'tractor',
                'bulldozer',
                'excavator',
                'grader',
                'loader',
                'ambulance',
                'police_vehicle',
                'fire_truck',
                'tow_truck',
                'trailer',
            ]),
            'subscription_plan' => fake()->randomElement(['basic', 'premium']),
            'status' => 'active',
        ];
    }
}
