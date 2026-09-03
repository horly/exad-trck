<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MobileDriverResource;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class MobileDriverController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'fleet_id' => ['nullable', 'integer', 'exists:fleets,id'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $user = $request->user();

        $drivers = Driver::query()
            ->visibleTo($user)
            ->with([
                'fleet:id,name,code',
                'department:id,name,code',
                'vehicles' => fn ($query) => $query
                    ->visibleTo($user)
                    ->select(['vehicles.id', 'vehicles.name', 'vehicles.registration_number'])
                    ->orderBy('vehicles.name'),
            ])
            ->when($search !== '', function ($query) use ($search, $user): void {
                $query->where(function ($query) use ($search, $user): void {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('fleet', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('department', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('vehicles', fn ($query) => $query
                            ->visibleTo($user)
                            ->where(function ($query) use ($search): void {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('registration_number', 'like', "%{$search}%");
                            }));
                });
            })
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when($user->isSuperadmin() && isset($filters['fleet_id']), fn ($query) => $query->where('fleet_id', $filters['fleet_id']))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->withQueryString();

        return MobileDriverResource::collection($drivers);
    }
}
