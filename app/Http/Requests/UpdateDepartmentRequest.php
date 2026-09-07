<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDepartmentRequest extends StoreDepartmentRequest
{
    public function authorize(): bool
    {
        /** @var Department|null $department */
        $department = $this->route('department');

        return $department !== null
            && (bool) $this->user()?->can('update-department', $department);
    }

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

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var Department|null $department */
            $department = $this->route('department');

            if ($department === null
                || $validator->errors()->has('fleet_id')
                || (int) $department->fleet_id === $this->integer('fleet_id')) {
                return;
            }

            if ($department->drivers()->exists()) {
                $validator->errors()->add('fleet_id', __('departments.fleet_change_blocked'));
            }
        }];
    }
}
