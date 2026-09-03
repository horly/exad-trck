<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceImmobilizationProfile extends Model
{
    protected $attributes = [
        'immobilize_dout1' => true,
        'immobilize_dout2' => true,
        'release_dout1' => false,
        'release_dout2' => false,
        'is_active' => true,
    ];

    protected $fillable = [
        'device_id',
        'immobilize_dout1',
        'immobilize_dout2',
        'release_dout1',
        'release_dout2',
        'is_active',
        'verified_by',
        'verified_at',
        'verification_note',
    ];

    protected function casts(): array
    {
        return [
            'immobilize_dout1' => 'boolean',
            'immobilize_dout2' => 'boolean',
            'release_dout1' => 'boolean',
            'release_dout2' => 'boolean',
            'is_active' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** @return array{1: int, 2: int} */
    public function outputsFor(string $action): array
    {
        return $action === DeviceCommand::ACTION_IMMOBILIZE
            ? [1 => (int) $this->immobilize_dout1, 2 => (int) $this->immobilize_dout2]
            : [1 => (int) $this->release_dout1, 2 => (int) $this->release_dout2];
    }
}
