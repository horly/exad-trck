<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage-departments');
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->isAdmin()) {
            $this->merge(['fleet_id' => $this->user()->fleet_id]);
        }
    }

    public function rules(): array
    {
        return [
            'fleet_id' => ['required', 'integer', 'exists:fleets,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments')->where('fleet_id', $this->integer('fleet_id')),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('departments')->where('fleet_id', $this->integer('fleet_id')),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
