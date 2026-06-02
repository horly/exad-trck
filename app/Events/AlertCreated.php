<?php

namespace App\Events;

use App\Models\Alert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Alert $alert)
    {
        $this->alert->loadMissing(['fleet:id,name,code', 'vehicle:id,name,registration_number', 'device:id,imei,name,status']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('superadmin.alerts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'alert.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'alert' => [
                'id' => $this->alert->id,
                'type' => $this->alert->type,
                'severity' => $this->alert->severity,
                'status' => $this->alert->status,
                'title' => $this->alert->localizedTitle(),
                'message' => $this->alert->localizedMessage(),
                'fleet' => $this->alert->fleet?->name,
                'vehicle' => $this->alert->vehicle?->name,
                'registration' => $this->alert->vehicle?->registration_number,
                'device' => $this->alert->device?->name ?: $this->alert->device?->imei,
                'latitude' => $this->alert->latitude,
                'longitude' => $this->alert->longitude,
                'occurred_at' => $this->alert->occurred_at?->toDateTimeString(),
            ],
        ];
    }
}
