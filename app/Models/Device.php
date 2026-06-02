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
            'settings' => 'array',
        ];
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
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
