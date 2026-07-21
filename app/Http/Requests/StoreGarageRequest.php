<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGarageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasClientPermission(\App\Models\User::PERMISSION_GARAGES_MANAGE);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'specialties' => collect(explode(',', (string) $this->input('specialties')))
                ->map(fn (string $specialty): string => trim($specialty))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('garages', 'name')],
            'type' => ['required', Rule::in(['internal', 'external'])],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'dispatcher_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
