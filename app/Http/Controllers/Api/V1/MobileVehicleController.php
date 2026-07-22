<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MobileVehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class MobileVehicleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'maintenance'])],
            'tracking_status' => ['nullable', Rule::in(['online', 'offline', 'inactive', 'maintenance', 'not_configured'])],
            'fleet_id' => ['nullable', 'integer', 'exists:fleets,id'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $trackingStatus = $filters['tracking_status'] ?? null;
        $user = $request->user();

        $vehicles = Vehicle::query()
            ->visibleTo($user)
            ->with(['fleet:id,name,code', 'device'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when($user->isSuperadmin() && isset($filters['fleet_id']), fn ($query) => $query->where('fleet_id', $filters['fleet_id']))
            ->when($trackingStatus === 'not_configured', fn ($query) => $query->whereDoesntHave('device'))
            ->when($trackingStatus !== null && $trackingStatus !== 'not_configured', fn ($query) => $query
                ->whereHas('device', fn ($query) => $query->where('status', $trackingStatus)))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->withQueryString();

        return MobileVehicleResource::collection($vehicles);
    }

    public function show(Request $request, int $vehicle): MobileVehicleResource
    {
        $model = Vehicle::query()
            ->visibleTo($request->user())
            ->with(['fleet:id,name,code', 'device'])
            ->findOrFail($vehicle);

        return new MobileVehicleResource($model);
    }
}
