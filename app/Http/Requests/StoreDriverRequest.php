<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperadmin();
    }

    protected function prepareForValidation(): void
    {
        $tags = collect(explode(',', (string) $this->input('tags')))
            ->map(fn (string $tag): string => trim($tag))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'rfid_uid' => $this->normalizeIdentifier($this->input('rfid_uid')),
            'tags' => $tags,
        ]);
    }

    public function rules(): array
    {
        return [
            'fleet_id' => ['required', 'integer', 'exists:fleets,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'first_name' => ['required', 'string', 'max:120'],
            'middle_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'employee_id' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('drivers')->where('fleet_id', $this->integer('fleet_id')),
            ],
            'social_security_number' => ['nullable', 'string', 'max:120'],
            'rfid_uid' => ['nullable', 'string', 'max:100', 'unique:driver_identifiers,uid'],
            'identifier_type' => ['required', Rule::in(['rfid', 'ibutton', 'nfc'])],
            'authorized_vehicle_ids' => ['nullable', 'array'],
            'authorized_vehicle_ids.*' => ['integer', 'distinct', 'exists:vehicles,id'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'location_latitude' => ['nullable', 'required_with:location_longitude', 'numeric', 'between:-90,90'],
            'location_longitude' => ['nullable', 'required_with:location_latitude', 'numeric', 'between:-180,180'],
            'location_radius_meters' => ['nullable', 'integer', 'min:10', 'max:10000'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_type' => ['nullable', 'string', 'max:80'],
            'license_issued_at' => ['nullable', 'date'],
            'license_expires_at' => ['nullable', 'date', 'after_or_equal:license_issued_at'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:60'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $fleetId = $this->integer('fleet_id');

            if ($this->filled('department_id') && ! Department::query()
                ->whereKey($this->integer('department_id'))
                ->where('fleet_id', $fleetId)
                ->exists()) {
                $validator->errors()->add('department_id', __('drivers.department_fleet_error'));
            }

            $vehicleIds = collect($this->input('authorized_vehicle_ids', []))->map(fn ($id): int => (int) $id)->unique();
            $validVehicleCount = Vehicle::query()
                ->where('fleet_id', $fleetId)
                ->whereKey($vehicleIds->all())
                ->count();

            if ($validVehicleCount !== $vehicleIds->count()) {
                $validator->errors()->add('authorized_vehicle_ids', __('drivers.vehicle_fleet_error'));
            }
        }];
    }

    private function normalizeIdentifier(mixed $value): ?string
    {
        $normalized = strtoupper((string) preg_replace('/[\s:\-]+/', '', trim((string) $value)));

        return $normalized !== '' ? $normalized : null;
    }
}
