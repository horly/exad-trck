<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\Subscription;
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
            'subscription_id' => Subscription::factory(),
            'fleet_id' => null,
            'brand' => 'teltonika',
            'name' => fake()->randomElement(['Camion Kin 01', 'Pick-up Gombe', 'Bus Matadi', 'Moto Livraison']),
            'model' => fake()->randomElement(['FMB920', 'FMC130', 'FMM130', 'FTC920']),
            'sim_number' => fake()->numerify('+243#########'),
            'operator_name' => fake()->randomElement(['Vodacom', 'Airtel', 'Orange', 'Africell']),
            'protocol' => 'TCP',
            'codec' => fake()->randomElement(['Codec8', 'GT06', 'JT808']),
            'status' => 'inactive',
            'last_seen_at' => fake()->dateTimeBetween('-2 days'),
            'last_position_at' => fake()->dateTimeBetween('-2 days'),
            'last_latitude' => fake()->latitude(-4.55, -4.20),
            'last_longitude' => fake()->longitude(15.15, 15.55),
            'last_speed' => fake()->numberBetween(0, 85),
            'last_angle' => fake()->numberBetween(0, 359),
            'last_ignition' => fake()->boolean(),
            'last_movement' => fake()->boolean(),
            'last_satellites' => fake()->numberBetween(5, 18),
            'last_gsm_signal' => fake()->numberBetween(45, 100),
            'last_battery_level' => fake()->numberBetween(35, 100),
            'last_external_voltage' => fake()->randomFloat(3, 11, 28),
            'last_battery_voltage' => fake()->randomFloat(3, 3, 5),
            'last_address' => fake()->streetAddress(),
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
            'last_movement' => false,
        ]);
    }

    public function moving(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'online',
            'last_speed' => 42,
            'last_movement' => true,
        ]);
    }
}
