<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Garage extends Model
{
    use HasFactory;

    protected $fillable = [
        'fleet_id', 'created_by',
        'name', 'type', 'responsible_name', 'dispatcher_name', 'phone', 'email',
        'address', 'latitude', 'longitude', 'specialties', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'specialties' => 'array',
        ];
    }

    public function maintenancePlans(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class);
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

        return $query->where(function (Builder $query) use ($user): void {
            $query->whereNull('fleet_id')->orWhere('fleet_id', $user->fleet_id ?? 0);
        });
    }
}
