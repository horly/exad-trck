<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MobileSession extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'access_token_id',
        'refresh_token_id',
        'device_identifier',
        'device_name',
        'platform',
        'app_version',
        'ip_address',
        'user_agent',
        'last_used_at',
        'access_expires_at',
        'refresh_expires_at',
        'revoked_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (MobileSession $session): void {
            $session->id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'access_expires_at' => 'datetime',
            'refresh_expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where('refresh_expires_at', '>', now());
    }
}
