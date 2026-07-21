<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteMaintenancePlanRequest extends FormRequest
{
    protected $errorBag = 'completion';

    public function authorize(): bool
    {
        return (bool) $this->user()?->hasClientPermission(\App\Models\User::PERMISSION_MAINTENANCE_MANAGE);
    }

    public function rules(): array
    {
        return [
            'performed_at' => ['required', 'date'],
            'garage_id' => ['nullable', 'integer', 'exists:garages,id'],
            'odometer_km' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'engine_hours' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'actual_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'documents' => ['nullable', 'array', 'max:6'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:16384'],
        ];
    }

}
