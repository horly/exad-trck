<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenancePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id', 'garage_id', 'created_by', 'name', 'description', 'maintenance_type',
        'estimated_cost', 'is_recurring', 'next_due_date', 'reminder_days', 'interval_days',
        'next_due_odometer_km', 'reminder_odometer_km', 'interval_odometer_km',
        'next_due_engine_hours', 'reminder_engine_hours', 'interval_engine_hours',
        'status', 'due_status', 'due_alert_sent_at', 'overdue_alert_sent_at', 'last_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'is_recurring' => 'boolean',
            'next_due_date' => 'date',
            'next_due_odometer_km' => 'decimal:2',
            'next_due_engine_hours' => 'decimal:2',
            'due_alert_sent_at' => 'datetime',
            'overdue_alert_sent_at' => 'datetime',
            'last_completed_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MaintenanceDocument::class);
    }
}
