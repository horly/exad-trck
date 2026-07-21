<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMaintenancePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasClientPermission(\App\Models\User::PERMISSION_MAINTENANCE_MANAGE);
    }

    protected function prepareForValidation(): void
    {
        $nullableNumbers = [
            'estimated_cost', 'interval_days', 'next_due_odometer_km', 'interval_odometer_km',
            'next_due_engine_hours', 'interval_engine_hours',
        ];
        $normalized = [
            'is_recurring' => $this->boolean('is_recurring'),
            'reminder_days' => $this->input('reminder_days', 0) ?: 0,
            'reminder_odometer_km' => $this->input('reminder_odometer_km', 0) ?: 0,
            'reminder_engine_hours' => $this->input('reminder_engine_hours', 0) ?: 0,
        ];

        foreach ($nullableNumbers as $field) {
            $normalized[$field] = $this->input($field) === '' ? null : $this->input($field);
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'garage_id' => ['nullable', 'integer', 'exists:garages,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'maintenance_type' => ['required', Rule::in(['preventive', 'corrective'])],
            'estimated_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'is_recurring' => ['boolean'],
            'next_due_date' => ['nullable', 'date'],
            'reminder_days' => ['integer', 'min:0', 'max:3650'],
            'interval_days' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'next_due_odometer_km' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'reminder_odometer_km' => ['integer', 'min:0', 'max:1000000'],
            'interval_odometer_km' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'next_due_engine_hours' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'reminder_engine_hours' => ['integer', 'min:0', 'max:100000'],
            'interval_engine_hours' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'documents' => ['nullable', 'array', 'max:6'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:16384'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('maintenance_type') === 'preventive'
                && ! $this->filled('next_due_date')
                && ! $this->filled('next_due_odometer_km')
                && ! $this->filled('next_due_engine_hours')) {
                $validator->errors()->add('next_due_date', __('maintenance.trigger_required'));
            }

            if (! $this->boolean('is_recurring')) {
                return;
            }

            foreach ([
                'next_due_date' => 'interval_days',
                'next_due_odometer_km' => 'interval_odometer_km',
                'next_due_engine_hours' => 'interval_engine_hours',
            ] as $trigger => $interval) {
                if ($this->filled($trigger) && ! $this->filled($interval)) {
                    $validator->errors()->add($interval, __('maintenance.interval_required'));
                }
            }
        }];
    }
}
