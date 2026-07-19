<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class AlertRule extends Model
{
    use HasFactory;

    public const CATEGORY_EQUIPMENT = 'equipment';
    public const CATEGORY_VEHICLE = 'vehicle';

    public const SCOPE_ALL = 'all';
    public const SCOPE_FLEET = 'fleet';
    public const SCOPE_VEHICLE = 'vehicle';
    public const SCOPE_DEVICE = 'device';

    /**
     * @var list<string>
     */
    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];

    /**
     * @var list<string>
     */
    public const CHANNELS = ['platform', 'email', 'sms', 'webhook'];

    /**
     * @var list<string>
     */
    public const SCHEDULE_DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fleet_id',
        'vehicle_id',
        'device_id',
        'name',
        'type',
        'category',
        'severity',
        'scope_type',
        'threshold_value',
        'threshold_unit',
        'channels',
        'schedule_days',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'threshold_value' => 'decimal:2',
            'channels' => 'array',
            'schedule_days' => 'array',
            'is_active' => 'boolean',
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

    public function states(): HasMany
    {
        return $this->hasMany(AlertRuleState::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_active')
            ->orderBy('category')
            ->orderBy('name');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where('scope_type', self::SCOPE_ALL)
                ->orWhereHas('fleet', fn (Builder $query): Builder => $query->visibleTo($user))
                ->orWhereHas('vehicle', fn (Builder $query): Builder => $query->visibleTo($user))
                ->orWhereHas('device', fn (Builder $query): Builder => $query->visibleTo($user));
        });
    }

    public function scopeLabel(): string
    {
        return match ($this->scope_type) {
            self::SCOPE_FLEET => $this->fleet?->name ?: __('alert_rules.scope_fleet'),
            self::SCOPE_VEHICLE => $this->vehicle?->name ?: __('alert_rules.scope_vehicle'),
            self::SCOPE_DEVICE => $this->device?->name ?: $this->device?->imei ?: __('alert_rules.scope_device'),
            default => __('alert_rules.scope_all'),
        };
    }

    public function thresholdLabel(): string
    {
        if ($this->threshold_value === null) {
            return __('alert_rules.no_threshold');
        }

        return trim((string) ((float) $this->threshold_value).' '.($this->threshold_unit ?? ''));
    }

    public function scheduleLabel(): string
    {
        $days = collect($this->schedule_days ?? []);

        if ($days->isEmpty()) {
            return __('alert_rules.schedule_always');
        }

        return $days
            ->map(fn (string $day): string => __('alert_rules.day_'.$day))
            ->implode(', ');
    }

    public static function defaultDefinitions(): Collection
    {
        return collect([
            [
                'name' => 'Aucun signal',
                'type' => 'no_signal',
                'category' => self::CATEGORY_EQUIPMENT,
                'severity' => 'high',
                'threshold_value' => 30,
                'threshold_unit' => 'min',
            ],
            [
                'name' => 'Signal GSM faible',
                'type' => 'weak_gsm',
                'category' => self::CATEGORY_EQUIPMENT,
                'severity' => 'medium',
                'threshold_value' => 35,
                'threshold_unit' => '%',
            ],
            [
                'name' => 'Batterie faible',
                'type' => 'low_battery',
                'category' => self::CATEGORY_EQUIPMENT,
                'severity' => 'medium',
                'threshold_value' => 20,
                'threshold_unit' => '%',
            ],
            [
                'name' => 'Coupure alimentation externe',
                'type' => 'external_power_cut',
                'category' => self::CATEGORY_EQUIPMENT,
                'severity' => 'critical',
            ],
            [
                'name' => 'OBD déconnecté',
                'type' => 'obd_disconnected',
                'category' => self::CATEGORY_EQUIPMENT,
                'severity' => 'high',
            ],
            [
                'name' => 'Brouillage GPS / GSM',
                'type' => 'jamming',
                'category' => self::CATEGORY_EQUIPMENT,
                'severity' => 'critical',
            ],
            [
                'name' => 'Excès de vitesse',
                'type' => 'overspeed',
                'category' => self::CATEGORY_VEHICLE,
                'severity' => 'high',
                'threshold_value' => 80,
                'threshold_unit' => 'km/h',
            ],
            [
                'name' => 'Ralenti prolongé',
                'type' => 'idle_engine_on',
                'category' => self::CATEGORY_VEHICLE,
                'severity' => 'medium',
                'threshold_value' => 10,
                'threshold_unit' => 'min',
            ],
            [
                'name' => 'Porte ouverte',
                'type' => 'door_open',
                'category' => self::CATEGORY_VEHICLE,
                'severity' => 'medium',
            ],
            [
                'name' => 'Freinage brusque',
                'type' => 'harsh_braking',
                'category' => self::CATEGORY_VEHICLE,
                'severity' => 'high',
            ],
            [
                'name' => 'Collision détectée',
                'type' => 'crash_detected',
                'category' => self::CATEGORY_VEHICLE,
                'severity' => 'critical',
            ],
            [
                'name' => 'SOS',
                'type' => 'sos',
                'category' => self::CATEGORY_VEHICLE,
                'severity' => 'critical',
            ],
        ])->map(fn (array $rule): array => array_merge([
            'scope_type' => self::SCOPE_ALL,
            'channels' => ['platform'],
            'schedule_days' => [],
            'is_active' => true,
        ], $rule));
    }
}
