<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MobileEventResource;
use App\Models\TrackerEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MobileEventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'vehicle_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'max:80'],
            'after_id' => ['nullable', 'integer', 'min:0'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $events = TrackerEvent::query()
            ->vehicleEvents()
            ->visibleTo($request->user())
            ->with(['fleet:id,name,code', 'vehicle:id,name,registration_number'])
            ->when(isset($filters['vehicle_id']), fn ($query) => $query->where('vehicle_id', $filters['vehicle_id']))
            ->when(isset($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when(isset($filters['after_id']), fn ($query) => $query->where('id', '>', $filters['after_id']))
            ->latest('started_at')
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->withQueryString();

        return MobileEventResource::collection($events);
    }
}
