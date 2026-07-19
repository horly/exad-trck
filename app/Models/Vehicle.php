<?php

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fleet_id',
        'created_by',
        'name',
        'registration_number',
        'brand',
        'model',
        'color',
        'year',
        'vehicle_type',
        'subscription_plan',
        'status',
    ];

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function speedPolicy(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'speed_policy_rule_id');
    }

    public function device(): HasOne
    {
        return $this->hasOne(Device::class);
    }

    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class)->withTimestamps();
    }

    public function driverSessions(): HasMany
    {
        return $this->hasMany(DriverSession::class);
    }

    public function maintenancePlans(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        return $query->whereHas('fleet', fn (Builder $query): Builder => $query->visibleTo($user));
    }
}
