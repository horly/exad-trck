<?php

namespace Database\Seeders;

use App\Models\VehicleSubscriptionFeature;
use Illuminate\Database\Seeder;

class VehicleSubscriptionFeatureSeeder extends Seeder
{
    public function run(): void
    {
        foreach (VehicleSubscriptionFeature::defaultFeatures() as $code => $feature) {
            VehicleSubscriptionFeature::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $feature['name'],
                    'description' => $feature['description'],
                    'sort_order' => $feature['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
