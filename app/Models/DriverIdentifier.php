<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverIdentifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'type',
        'uid',
        'active',
        'issued_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(DriverSession::class);
    }
}
