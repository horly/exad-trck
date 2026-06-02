<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Lang;

class Alert extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fleet_id',
        'vehicle_id',
        'device_id',
        'position_id',
        'acknowledged_by',
        'type',
        'severity',
        'status',
        'title',
        'message',
        'latitude',
        'longitude',
        'speed',
        'metadata',
        'occurred_at',
        'acknowledged_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function localizedTitle(): string
    {
        $titleKey = (string) data_get($this->metadata, 'translation.title_key', '');

        if ($titleKey !== '' && Lang::has($titleKey)) {
            return __($titleKey);
        }

        $legacyTranslation = $this->legacyTranslation();

        if ($legacyTranslation !== null && Lang::has($legacyTranslation['title_key'])) {
            return __($legacyTranslation['title_key']);
        }

        return $this->title;
    }

    public function localizedMessage(): string
    {
        $messageKey = (string) data_get($this->metadata, 'translation.message_key', '');

        if ($messageKey !== '' && Lang::has($messageKey)) {
            return __($messageKey, $this->localizedReplacements());
        }

        $legacyTranslation = $this->legacyTranslation();

        if ($legacyTranslation !== null && Lang::has($legacyTranslation['message_key'])) {
            return __($legacyTranslation['message_key'], $this->fallbackReplacements());
        }

        return $this->message;
    }

    /**
     * @return array<string, string|int|float>
     */
    private function localizedReplacements(): array
    {
        $replacements = data_get($this->metadata, 'translation.replace', []);

        if (! is_array($replacements)) {
            return [];
        }

        return collect($replacements)
            ->map(function (mixed $value): string|int|float {
                if (is_array($value)) {
                    $translationKey = (string) ($value['trans_key'] ?? '');

                    if ($translationKey !== '' && Lang::has($translationKey)) {
                        return __($translationKey);
                    }
                }

                return is_scalar($value) ? $value : '';
            })
            ->all();
    }

    /**
     * @return array{title_key: string, message_key: string}|null
     */
    private function legacyTranslation(): ?array
    {
        $title = str($this->title)->lower()->squish()->value();
        $message = str($this->message)->lower()->squish()->value();

        return match ($this->type) {
            'no_signal' => $title === 'no signal' || str_contains($message, 'is no longer transmitting signal')
                ? ['title_key' => 'alerts.type_no_signal', 'message_key' => 'alerts.message_no_signal']
                : null,
            'signal_recovered' => $title === 'signal restored' || str_contains($message, 'is connected again')
                ? ['title_key' => 'alerts.type_signal_recovered', 'message_key' => 'alerts.message_signal_recovered']
                : null,
            'overspeed' => $title === 'overspeed' || str_contains($message, 'above the')
                ? ['title_key' => 'alerts.type_overspeed', 'message_key' => 'alerts.message_overspeed']
                : null,
            'sos' => $title === 'sos' || str_contains($message, 'demo alert received')
                ? ['title_key' => 'alerts.type_sos', 'message_key' => 'alerts.message_demo']
                : null,
            default => null,
        };
    }

    /**
     * @return array<string, string|int|float>
     */
    private function fallbackReplacements(): array
    {
        return [
            'tracker' => $this->device?->name ?: $this->device?->imei ?: (string) data_get($this->metadata, 'imei', ''),
            'vehicle' => $this->vehicle?->name ?: __('alerts.unknown_vehicle'),
            'speed' => $this->speed ?? 0,
            'limit' => (int) data_get($this->metadata, 'speed_limit', 0),
        ];
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->whereHas('fleet', fn (Builder $query): Builder => $query->visibleTo($user))
                ->orWhereHas('vehicle', fn (Builder $query): Builder => $query->visibleTo($user))
                ->orWhereHas('device', fn (Builder $query): Builder => $query->visibleTo($user));
        });
    }
}
