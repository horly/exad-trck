<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
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
}
