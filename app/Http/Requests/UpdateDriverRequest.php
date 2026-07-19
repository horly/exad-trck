<?php

namespace App\Http\Requests;

use App\Models\Driver;
use Illuminate\Validation\Rule;

class UpdateDriverRequest extends StoreDriverRequest
{
    public function rules(): array
    {
        /** @var Driver|null $driver */
        $driver = $this->route('driver');
        $identifierId = $driver?->identifiers()
            ->where('uid', (string) $this->input('rfid_uid'))
            ->value('id');

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
                Rule::unique('drivers')
                    ->where('fleet_id', $this->integer('fleet_id'))
                    ->ignore($driver),
            ],
            'social_security_number' => ['nullable', 'string', 'max:120'],
            'rfid_uid' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('driver_identifiers', 'uid')->ignore($identifierId),
            ],
            'identifier_type' => ['required', Rule::in(['rfid', 'ibutton', 'nfc'])],
            'authorized_vehicle_ids' => ['nullable', 'array'],
            'authorized_vehicle_ids.*' => ['integer', 'distinct', 'exists:vehicles,id'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
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
}
