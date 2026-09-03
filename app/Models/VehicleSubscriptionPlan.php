<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VehicleSubscriptionPlan extends Model
{
    /**
     * @var list<string>
     */
    public const FEATURES = [
        'realtime_tracking',
        'data_history',
        'speed_limit',
        'remote_engine_stop',
        'geofence',
        'notifications_alerts',
        'export_reports',
        'driver_identification',
        'sos_button',
        'maintenance_planning',
        'fuel_management',
        'driver_behavior',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'features',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return list<string>
     */
    public static function featureKeys(): array
    {
        $features = VehicleSubscriptionFeature::query()
            ->active()
            ->ordered()
            ->pluck('code')
            ->all();

        return $features !== [] ? $features : self::FEATURES;
    }

    public static function includesFeature(string $planCode, string $feature): bool
    {
        $plan = static::query()->where('code', $planCode)->first(['features']);
        $features = $plan?->features
            ?? data_get(static::defaultPlans(), $planCode.'.features', []);

        return in_array($feature, is_array($features) ? $features : [], true);
    }

    /**
     * @return array<string, array{name: string, description: string, color: string, sort_order: int, features: list<string>}>
     */
    public static function defaultPlans(): array
    {
        $basic = [
            'realtime_tracking',
            'data_history',
            'speed_limit',
            'remote_engine_stop',
            'geofence',
            'notifications_alerts',
            'export_reports',
        ];

        $standard = [
            ...$basic,
            'driver_identification',
            'sos_button',
            'maintenance_planning',
        ];

        return [
            'basic' => [
                'name' => 'Basique',
                'description' => 'Suivi GPS essentiel pour les véhicules standards.',
                'color' => '#171064',
                'sort_order' => 10,
                'features' => $basic,
            ],
            'standard' => [
                'name' => 'Standard',
                'description' => 'Fonctionnalités avancées pour les flottes opérationnelles.',
                'color' => '#137f86',
                'sort_order' => 20,
                'features' => $standard,
            ],
            'premium' => [
                'name' => 'Premium',
                'description' => 'Couverture complète avec analyses conducteur et carburant.',
                'color' => '#ffd426',
                'sort_order' => 30,
                'features' => [
                    ...$standard,
                    'fuel_management',
                    'driver_behavior',
                ],
            ],
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
