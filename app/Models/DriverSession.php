<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'driver_identifier_id',
        'vehicle_id',
        'device_id',
        'start_position_id',
        'end_position_id',
        'started_at',
        'ended_at',
        'status',
        'geofence_status',
        'geofence_distance_meters',
        'geofence_updated_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'geofence_distance_meters' => 'integer',
            'geofence_updated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function identifier(): BelongsTo
    {
        return $this->belongsTo(DriverIdentifier::class, 'driver_identifier_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function startPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'start_position_id');
    }

    public function endPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'end_position_id');
    }
}
