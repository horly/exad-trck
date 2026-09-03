<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileDriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'employee_id' => $this->employee_id,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'fleet' => $this->fleet ? [
                'id' => $this->fleet->id,
                'name' => $this->fleet->name,
                'code' => $this->fleet->code,
            ] : null,
            'department' => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
                'code' => $this->department->code,
            ] : null,
            'vehicles' => $this->vehicles->map(fn ($vehicle): array => [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'registration_number' => $vehicle->registration_number,
            ])->values(),
        ];
    }
}
