<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'protocol' => 'TCP',
        'status' => 'inactive',
        'last_speed' => 0,
        'last_angle' => 0,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'subscription_id',
        'fleet_id',
        'vehicle_id',
        'brand',
        'imei',
        'name',
        'model',
        'sim_number',
        'operator_name',
        'protocol',
        'codec',
        'status',
        'last_seen_at',
        'last_position_at',
        'last_latitude',
        'last_longitude',
        'last_speed',
        'last_angle',
        'last_ignition',
        'last_movement',
        'last_satellites',
        'last_gsm_signal',
        'last_battery_level',
        'last_external_voltage',
        'last_battery_voltage',
        'last_odometer_km',
        'last_engine_seconds',
        'last_obd_runtime_seconds',
        'last_obd_rpm',
        'last_obd_speed',
        'last_obd_throttle_percent',
        'last_obd_engine_temperature_c',
        'last_obd_module_voltage',
        'last_obd_engine_load_percent',
        'last_obd_fault_distance_km',
        'last_obd_errors_count',
        'last_obd_distance_since_clear_km',
        'last_can_fuel_level_percent',
        'last_can_total_mileage_km',
        'last_obd_updated_at',
        'last_diagnostic_updated_at',
        'last_sensors',
        'last_io',
        'last_raw_payload',
        'last_address',
        'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'last_position_at' => 'datetime',
            'last_latitude' => 'decimal:7',
            'last_longitude' => 'decimal:7',
            'last_ignition' => 'boolean',
            'last_movement' => 'boolean',
            'last_satellites' => 'integer',
            'last_gsm_signal' => 'integer',
            'last_battery_level' => 'integer',
            'last_external_voltage' => 'decimal:3',
            'last_battery_voltage' => 'decimal:3',
            'last_odometer_km' => 'decimal:2',
            'last_engine_seconds' => 'integer',
            'last_obd_runtime_seconds' => 'integer',
            'last_obd_rpm' => 'integer',
            'last_obd_speed' => 'integer',
            'last_obd_throttle_percent' => 'decimal:2',
            'last_obd_engine_temperature_c' => 'decimal:2',
            'last_obd_module_voltage' => 'decimal:3',
            'last_obd_engine_load_percent' => 'decimal:2',
            'last_obd_fault_distance_km' => 'integer',
            'last_obd_errors_count' => 'integer',
            'last_obd_distance_since_clear_km' => 'integer',
            'last_can_fuel_level_percent' => 'decimal:2',
            'last_can_total_mileage_km' => 'decimal:2',
            'last_obd_updated_at' => 'datetime',
            'last_diagnostic_updated_at' => 'datetime',
            'last_sensors' => 'array',
            'last_io' => 'array',
            'last_raw_payload' => 'array',
            'settings' => 'array',
        ];
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function trackerEvents(): HasMany
    {
        return $this->hasMany(TrackerEvent::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where('devices.subscription_id', $user->subscription_id)
                ->orWhereHas('fleet.users', fn (Builder $query): Builder => $query->whereKey($user->id))
                ->orWhereHas('vehicle.fleet.users', fn (Builder $query): Builder => $query->whereKey($user->id));
        });
    }
}
