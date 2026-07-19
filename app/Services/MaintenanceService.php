<?php

namespace App\Services;

use App\Models\MaintenanceDocument;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRecord;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MaintenanceService
{
    public function __construct(private readonly AlertService $alerts) {}

    public function evaluateAll(): int
    {
        $evaluated = 0;

        MaintenancePlan::query()
            ->where('status', 'active')
            ->with(['vehicle.device', 'garage'])
            ->chunkById(100, function ($plans) use (&$evaluated): void {
                foreach ($plans as $plan) {
                    $this->evaluate($plan);
                    $evaluated++;
                }
            });

        return $evaluated;
    }

    public function evaluateVehicle(Vehicle $vehicle): void
    {
        $vehicle->maintenancePlans()
            ->where('status', 'active')
            ->with(['vehicle.device', 'garage'])
            ->each(fn (MaintenancePlan $plan) => $this->evaluate($plan));
    }

    public function evaluate(MaintenancePlan $plan): string
    {
        if ($plan->status !== 'active') {
            return $plan->due_status;
        }

        $plan->loadMissing(['vehicle.device', 'garage']);
        $status = $this->calculateStatus($plan);

        if ($plan->due_status !== $status) {
            $plan->forceFill(['due_status' => $status])->save();
        }

        if ($status === 'overdue' && $plan->overdue_alert_sent_at === null) {
            $this->alerts->createMaintenanceAlert($plan, true);
            $plan->forceFill(['overdue_alert_sent_at' => now()])->save();
        } elseif (in_array($status, ['due_soon', 'due'], true) && $plan->due_alert_sent_at === null) {
            $this->alerts->createMaintenanceAlert($plan);
            $plan->forceFill(['due_alert_sent_at' => now()])->save();
        }

        return $status;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $documents
     */
    public function complete(MaintenancePlan $plan, array $data, User $user, array $documents = []): MaintenanceRecord
    {
        $plan->loadMissing('vehicle.device');
        $performedAt = Carbon::parse($data['performed_at']);

        $record = DB::transaction(function () use ($plan, $data, $user, $performedAt): MaintenanceRecord {
            $record = MaintenanceRecord::query()->create([
                'maintenance_plan_id' => $plan->id,
                'vehicle_id' => $plan->vehicle_id,
                'garage_id' => $data['garage_id'] ?? $plan->garage_id,
                'created_by' => $user->id,
                'name' => $plan->name,
                'maintenance_type' => $plan->maintenance_type,
                'description' => $plan->description,
                'scheduled_due_date' => $plan->next_due_date,
                'scheduled_due_odometer_km' => $plan->next_due_odometer_km,
                'scheduled_due_engine_hours' => $plan->next_due_engine_hours,
                'performed_at' => $performedAt,
                'odometer_km' => $data['odometer_km'] ?? $plan->vehicle?->device?->last_odometer_km,
                'engine_hours' => $data['engine_hours'] ?? $this->deviceEngineHours($plan->vehicle),
                'estimated_cost' => $plan->estimated_cost,
                'actual_cost' => $data['actual_cost'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
            ]);

            $this->advancePlan($plan, $record, $performedAt);

            return $record;
        });

        $this->storeDocuments($record, $documents);

        return $record;
    }

    /** @param  array<int, UploadedFile>  $documents */
    public function storeDocuments(MaintenancePlan|MaintenanceRecord $owner, array $documents): void
    {
        foreach ($documents as $document) {
            $path = $document->store('maintenance', 'public');
            $attributes = [
                'disk' => 'public',
                'path' => $path,
                'original_name' => $document->getClientOriginalName(),
                'mime_type' => $document->getClientMimeType(),
                'size' => $document->getSize(),
            ];

            if ($owner instanceof MaintenancePlan) {
                $attributes['maintenance_plan_id'] = $owner->id;
            } else {
                $attributes['maintenance_record_id'] = $owner->id;
            }

            MaintenanceDocument::query()->create($attributes);
        }
    }

    public function deletePlan(MaintenancePlan $plan): void
    {
        $plan->loadMissing('documents');

        foreach ($plan->documents as $document) {
            Storage::disk($document->disk)->delete($document->path);
        }

        $plan->delete();
    }

    private function calculateStatus(MaintenancePlan $plan): string
    {
        if ($plan->maintenance_type === 'corrective') {
            return 'due';
        }

        $rank = ['scheduled' => 0, 'due_soon' => 1, 'due' => 2, 'overdue' => 3];
        $statuses = ['scheduled'];
        $today = today();

        if ($plan->next_due_date) {
            $statuses[] = $plan->next_due_date->isBefore($today)
                ? 'overdue'
                : ($plan->next_due_date->isSameDay($today)
                    ? 'due'
                    : ($plan->next_due_date->lte($today->copy()->addDays($plan->reminder_days)) ? 'due_soon' : 'scheduled'));
        }

        $odometer = $plan->vehicle?->device?->last_odometer_km;
        if ($plan->next_due_odometer_km !== null && $odometer !== null) {
            $remaining = (float) $plan->next_due_odometer_km - (float) $odometer;
            $statuses[] = $remaining <= 0 ? 'due' : ($remaining <= $plan->reminder_odometer_km ? 'due_soon' : 'scheduled');
        }

        $engineHours = $this->deviceEngineHours($plan->vehicle);
        if ($plan->next_due_engine_hours !== null && $engineHours !== null) {
            $remaining = (float) $plan->next_due_engine_hours - $engineHours;
            $statuses[] = $remaining <= 0 ? 'due' : ($remaining <= $plan->reminder_engine_hours ? 'due_soon' : 'scheduled');
        }

        return collect($statuses)->sortByDesc(fn (string $status): int => $rank[$status])->first();
    }

    private function advancePlan(MaintenancePlan $plan, MaintenanceRecord $record, Carbon $performedAt): void
    {
        if (! $plan->is_recurring) {
            $plan->forceFill([
                'status' => 'completed',
                'due_status' => 'completed',
                'last_completed_at' => $performedAt,
            ])->save();

            return;
        }

        $plan->forceFill([
            'next_due_date' => $plan->next_due_date && $plan->interval_days
                ? $performedAt->copy()->addDays($plan->interval_days)->toDateString()
                : null,
            'next_due_odometer_km' => $plan->next_due_odometer_km && $plan->interval_odometer_km
                ? ((float) ($record->odometer_km ?? $plan->next_due_odometer_km) + $plan->interval_odometer_km)
                : null,
            'next_due_engine_hours' => $plan->next_due_engine_hours && $plan->interval_engine_hours
                ? ((float) ($record->engine_hours ?? $plan->next_due_engine_hours) + $plan->interval_engine_hours)
                : null,
            'garage_id' => $record->garage_id ?? $plan->garage_id,
            'due_status' => 'scheduled',
            'due_alert_sent_at' => null,
            'overdue_alert_sent_at' => null,
            'last_completed_at' => $performedAt,
        ])->save();
    }

    private function deviceEngineHours(?Vehicle $vehicle): ?float
    {
        $seconds = $vehicle?->device?->last_engine_seconds;

        return $seconds !== null ? round($seconds / 3600, 2) : null;
    }
}
