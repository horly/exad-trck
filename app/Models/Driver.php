<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'fleet_id',
        'department_id',
        'first_name',
        'middle_name',
        'last_name',
        'employee_id',
        'social_security_number',
        'phone',
        'email',
        'address',
        'location_latitude',
        'location_longitude',
        'location_radius_meters',
        'license_number',
        'license_type',
        'license_issued_at',
        'license_expires_at',
        'tags',
        'photo_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'license_issued_at' => 'date',
            'license_expires_at' => 'date',
            'location_latitude' => 'decimal:7',
            'location_longitude' => 'decimal:7',
            'location_radius_meters' => 'integer',
            'tags' => 'array',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])->filter()->implode(' '));
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(DriverIdentifier::class);
    }

    public function primaryIdentifier(): HasOne
    {
        return $this->hasOne(DriverIdentifier::class)->ofMany(
            ['id' => 'max'],
            fn (Builder $query) => $query->where('active', true),
        );
    }

    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class)->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(DriverSession::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        return $query->where('drivers.fleet_id', $user->fleet_id ?? 0);
    }
}
