<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'device_id',
        'imei',
        'gps_time',
        'server_time',
        'latitude',
        'longitude',
        'address',
        'is_valid',
        'speed',
        'angle',
        'altitude',
        'satellites',
        'ignition',
        'movement',
        'external_voltage',
        'battery_voltage',
        'odometer',
        'raw_data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gps_time' => 'datetime',
            'server_time' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_valid' => 'boolean',
            'ignition' => 'boolean',
            'movement' => 'boolean',
            'external_voltage' => 'decimal:3',
            'battery_voltage' => 'decimal:3',
            'raw_data' => 'array',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
