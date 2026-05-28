<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'imei' => fake()->numerify('###############'),
            'gps_time' => fake()->dateTimeBetween('-12 hours'),
            'server_time' => fake()->dateTimeBetween('-12 hours'),
            'latitude' => fake()->latitude(-4.55, -4.20),
            'longitude' => fake()->longitude(15.15, 15.55),
            'is_valid' => true,
            'speed' => fake()->numberBetween(0, 90),
            'angle' => fake()->numberBetween(0, 359),
            'altitude' => fake()->numberBetween(250, 650),
            'satellites' => fake()->numberBetween(5, 18),
            'ignition' => fake()->boolean(),
            'movement' => fake()->boolean(),
            'external_voltage' => fake()->randomFloat(3, 11, 28),
            'battery_voltage' => fake()->randomFloat(3, 3, 5),
            'odometer' => fake()->numberBetween(1000, 900000),
            'raw_data' => [
                'source' => 'factory',
            ],
        ];
    }

    public function forDevice(Device $device): static
    {
        return $this->state(fn (array $attributes) => [
            'device_id' => $device->id,
            'imei' => $device->imei,
        ]);
    }
}
