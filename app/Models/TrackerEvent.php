<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Lang;

class TrackerEvent extends Model
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
        'type',
        'title',
        'message',
        'started_at',
        'ended_at',
        'duration_seconds',
        'latitude',
        'longitude',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'metadata' => 'array',
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

    public function localizedTitle(): string
    {
        $titleKey = (string) data_get($this->metadata, 'translation.title_key', '');

        if ($titleKey !== '' && Lang::has($titleKey)) {
            return __($titleKey);
        }

        return $this->title;
    }

    public function localizedMessage(): string
    {
        $messageKey = (string) data_get($this->metadata, 'translation.message_key', '');

        if ($messageKey !== '' && Lang::has($messageKey)) {
            return __($messageKey, $this->localizedReplacements());
        }

        return $this->message;
    }

    public function durationLabel(): ?string
    {
        if ($this->duration_seconds === null) {
            return null;
        }

        if ($this->duration_seconds < 60) {
            return __('trackers.duration_seconds', ['seconds' => $this->duration_seconds]);
        }

        $minutes = intdiv($this->duration_seconds, 60);
        $seconds = $this->duration_seconds % 60;

        if ($seconds === 0) {
            return __('trackers.duration_minutes', ['minutes' => $minutes]);
        }

        return __('trackers.duration_minutes_seconds', [
            'minutes' => $minutes,
            'seconds' => $seconds,
        ]);
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
