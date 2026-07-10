<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use Illuminate\Database\Seeder;

class AlertRuleSeeder extends Seeder
{
    public function run(): void
    {
        AlertRule::defaultDefinitions()->each(function (array $definition): void {
            AlertRule::query()->updateOrCreate(
                ['type' => $definition['type'], 'scope_type' => $definition['scope_type']],
                $definition,
            );
        });
    }
}
