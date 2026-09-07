<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceCommand extends Model
{
    public const ACTION_IMMOBILIZE = 'immobilize';

    public const ACTION_RELEASE = 'release';

    public const STATUS_PENDING_SAFETY = 'pending_safety';

    public const STATUS_READY = 'ready';

    public const STATUS_CLAIMED = 'claimed';

    public const STATUS_SENT = 'sent';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING_SAFETY,
        self::STATUS_READY,
        self::STATUS_CLAIMED,
        self::STATUS_SENT,
        self::STATUS_ACKNOWLEDGED,
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING_SAFETY,
        'attempts' => 0,
    ];

    protected $fillable = [
        'uuid', 'device_id', 'vehicle_id', 'fleet_id', 'requested_by', 'imei',
        'vehicle_label', 'action', 'status', 'command_text', 'desired_outputs',
        'reason', 'request_ip', 'request_user_agent', 'safety_snapshot',
        'safety_checked_at', 'attempts', 'claim_token', 'claimed_at', 'sent_at',
        'acknowledged_at', 'confirmed_at', 'expires_at', 'failed_at',
        'cancelled_at', 'failure_code', 'failure_message', 'response_text',
    ];

    protected $hidden = ['command_text', 'claim_token'];

    protected function casts(): array
    {
        return [
            'desired_outputs' => 'array',
            'safety_snapshot' => 'array',
            'safety_checked_at' => 'datetime',
            'claimed_at' => 'datetime',
            'sent_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'expires_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function commandAttempts(): HasMany
    {
        return $this->hasMany(DeviceCommandAttempt::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true)
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /** @return list<int> */
    public function targetOutputs(): array
    {
        return collect($this->desired_outputs ?? [])
            ->filter(fn (mixed $state): bool => $state !== null)
            ->keys()
            ->map(fn (int|string $output): int => (int) $output)
            ->filter(fn (int $output): bool => in_array($output, [1, 2], true))
            ->values()
            ->all();
    }

    public function targetsOutput(int $output): bool
    {
        return in_array($output, $this->targetOutputs(), true);
    }

    public function desiredStateFor(int $output): ?bool
    {
        $desired = $this->desired_outputs ?? [];
        $state = $desired[$output] ?? $desired[(string) $output] ?? null;

        return $state === null ? null : (bool) $state;
    }
}
