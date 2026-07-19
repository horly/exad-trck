<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_plan_id', 'maintenance_record_id', 'disk', 'path',
        'original_name', 'mime_type', 'size',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class, 'maintenance_plan_id');
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRecord::class, 'maintenance_record_id');
    }
}
