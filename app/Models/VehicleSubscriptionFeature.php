<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VehicleSubscriptionFeature extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, array{name: string, description: string, sort_order: int}>
     */
    public static function defaultFeatures(): array
    {
        return [
            'realtime_tracking' => [
                'name' => 'Suivi en temps réel',
                'description' => 'Position et état des véhicules en direct.',
                'sort_order' => 10,
            ],
            'data_history' => [
                'name' => 'Historique des données',
                'description' => 'Consultation des trajets et positions passées.',
                'sort_order' => 20,
            ],
            'speed_limit' => [
                'name' => 'Limitation de vitesse',
                'description' => 'Détection et suivi des dépassements de vitesse.',
                'sort_order' => 30,
            ],
            'remote_engine_stop' => [
                'name' => 'Arrêt moteur à distance',
                'description' => 'Commande sécurisée d’immobilisation à distance.',
                'sort_order' => 40,
            ],
            'geofence' => [
                'name' => 'Geofence',
                'description' => 'Zones géographiques avec entrées et sorties suivies.',
                'sort_order' => 50,
            ],
            'notifications_alerts' => [
                'name' => 'Notifications et alertes',
                'description' => 'Notifications opérationnelles et alertes système.',
                'sort_order' => 60,
            ],
            'export_reports' => [
                'name' => 'Rapport en fichier Excel ou PDF',
                'description' => 'Exports métier pour reporting et analyse.',
                'sort_order' => 70,
            ],
            'driver_identification' => [
                'name' => 'Identification et sécurité du conducteur',
                'description' => 'Suivi des conducteurs et accès sécurisé.',
                'sort_order' => 80,
            ],
            'sos_button' => [
                'name' => 'Bouton SOS',
                'description' => 'Signal SOS pour situations critiques.',
                'sort_order' => 90,
            ],
            'maintenance_planning' => [
                'name' => 'Planification et rappels d’entretien',
                'description' => 'Rappels et planification des entretiens.',
                'sort_order' => 100,
            ],
            'fuel_management' => [
                'name' => 'Gestion de carburant',
                'description' => 'Suivi des consommations et anomalies carburant.',
                'sort_order' => 110,
            ],
            'driver_behavior' => [
                'name' => 'Surveillance du comportement du conducteur',
                'description' => 'Analyse des habitudes de conduite et risques.',
                'sort_order' => 120,
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
