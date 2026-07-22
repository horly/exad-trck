<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->localizedTitle(),
            'message' => $this->localizedMessage(),
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
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'duration_seconds' => $this->duration_seconds,
        ];
    }
}
