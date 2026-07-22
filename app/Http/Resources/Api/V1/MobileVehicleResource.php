<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileVehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Device|null $device */
        $device = $this->device;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'registration_number' => $this->registration_number,
            'brand' => $this->brand,
            'model' => $this->model,
            'color' => $this->color,
            'year' => $this->year,
            'type' => $this->vehicle_type,
            'status' => $this->status,
            'fleet' => $this->fleet ? [
                'id' => $this->fleet->id,
                'name' => $this->fleet->name,
                'code' => $this->fleet->code,
            ] : null,
            'tracking' => [
                'configured' => $device !== null,
                'status' => $device?->status ?? 'not_configured',
                'online' => $device?->status === 'online',
                'speed_kmh' => $device ? (int) $device->last_speed : null,
                'heading' => $device ? (int) $device->last_angle : null,
                'ignition' => $device?->last_ignition,
                'movement' => $device?->last_movement,
                'last_signal_at' => $device?->last_seen_at?->toISOString(),
                'last_position_at' => $device?->last_position_at?->toISOString(),
                'position' => $device && $device->last_latitude !== null && $device->last_longitude !== null ? [
                    'latitude' => (float) $device->last_latitude,
                    'longitude' => (float) $device->last_longitude,
                    'address' => $device->last_address,
                ] : null,
            ],
        ];
    }
}
