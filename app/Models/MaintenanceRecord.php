<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_plan_id', 'vehicle_id', 'garage_id', 'created_by', 'name',
        'maintenance_type', 'description', 'scheduled_due_date', 'scheduled_due_odometer_km',
        'scheduled_due_engine_hours', 'performed_at', 'odometer_km', 'engine_hours',
        'estimated_cost', 'actual_cost', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_due_date' => 'date',
            'performed_at' => 'datetime',
            'scheduled_due_odometer_km' => 'decimal:2',
            'scheduled_due_engine_hours' => 'decimal:2',
            'odometer_km' => 'decimal:2',
            'engine_hours' => 'decimal:2',
            'estimated_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class, 'maintenance_plan_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MaintenanceDocument::class);
    }
}
