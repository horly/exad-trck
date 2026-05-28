<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'imei' => fake()->unique()->numerify('###############'),
            'name' => fake()->randomElement(['Camion Kin 01', 'Pick-up Gombe', 'Bus Matadi', 'Moto Livraison']),
            'model' => fake()->randomElement(['Teltonika FMB920', 'Queclink GV300', 'Concox GT06N']),
            'sim_number' => fake()->numerify('+243#########'),
            'operator_name' => fake()->randomElement(['Vodacom', 'Airtel', 'Orange', 'Africell']),
            'protocol' => 'TCP',
            'codec' => fake()->randomElement(['Codec8', 'GT06', 'JT808']),
            'status' => fake()->randomElement(['online', 'offline', 'maintenance']),
            'last_seen_at' => fake()->dateTimeBetween('-2 days'),
            'last_position_at' => fake()->dateTimeBetween('-2 days'),
            'last_latitude' => fake()->latitude(-4.55, -4.20),
            'last_longitude' => fake()->longitude(15.15, 15.55),
            'last_speed' => fake()->numberBetween(0, 85),
            'last_angle' => fake()->numberBetween(0, 359),
            'settings' => [
                'heartbeat_interval' => 60,
            ],
        ];
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'online',
            'last_speed' => 0,
        ]);
    }

    public function moving(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'online',
            'last_speed' => 42,
        ]);
    }
}
