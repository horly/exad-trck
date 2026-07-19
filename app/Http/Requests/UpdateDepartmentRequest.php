<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends StoreDepartmentRequest
{
    public function rules(): array
    {
        /** @var Department|null $department */
        $department = $this->route('department');

        return [
            'fleet_id' => ['required', 'integer', 'exists:fleets,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments')
                    ->where('fleet_id', $this->integer('fleet_id'))
                    ->ignore($department),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('departments')
                    ->where('fleet_id', $this->integer('fleet_id'))
                    ->ignore($department),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
