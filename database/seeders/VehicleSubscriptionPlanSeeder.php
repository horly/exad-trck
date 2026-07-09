<?php

namespace Database\Seeders;

use App\Models\VehicleSubscriptionPlan;
use Illuminate\Database\Seeder;

class VehicleSubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (VehicleSubscriptionPlan::defaultPlans() as $code => $plan) {
            VehicleSubscriptionPlan::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'color' => $plan['color'],
                    'features' => $plan['features'],
                    'sort_order' => $plan['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
