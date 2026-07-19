<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertRuleState extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'alert_rule_id',
        'device_id',
        'is_triggered',
        'last_value',
        'triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_triggered' => 'boolean',
            'last_value' => 'decimal:2',
            'triggered_at' => 'datetime',
        ];
    }

    public function alertRule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
