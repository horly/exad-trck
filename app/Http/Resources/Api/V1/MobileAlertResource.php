<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'severity' => $this->severity,
            'status' => $this->status,
            'title' => $this->localizedTitle(),
            'message' => $this->localizedMessageFor($request->user()),
            'vehicle' => $this->vehicle ? [
                'id' => $this->vehicle->id,
                'name' => $this->vehicle->name,
                'registration_number' => $this->vehicle->registration_number,
            ] : null,
            'fleet' => $this->fleet ? [
                'id' => $this->fleet->id,
                'name' => $this->fleet->name,
                'code' => $this->fleet->code,
            ] : null,
            'location' => $this->latitude !== null && $this->longitude !== null ? [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
            ] : null,
            'speed_kmh' => $this->speed !== null ? (int) $this->speed : null,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'acknowledged_at' => $this->acknowledged_at?->toISOString(),
        ];
    }
}
