<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGarageRequest;
use App\Http\Requests\UpdateGarageRequest;
use App\Models\Garage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GarageController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $garages = Garage::query()
            ->visibleTo($request->user())
            ->withCount(['maintenancePlans as active_maintenance_count' => fn ($query) => $query->where('status', 'active')])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('responsible_name', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(5)
            ->withQueryString();
        $data = [
            'garages' => $garages,
            'search' => $search,
        ];

        if ($request->ajax()) {
            return response()->json(['html' => view('garages.partials.table', $data)->render()]);
        }

        return view('garages.index', $data);
    }

    public function store(StoreGarageRequest $request): RedirectResponse
    {
        Garage::query()->create([
            ...$request->validated(),
            'fleet_id' => $request->user()->isSuperadmin() ? null : $request->user()->fleet_id,
            'created_by' => $request->user()->id,
        ]);

        return to_route('garages.index')->with('status', __('garages.created'));
    }

    public function update(UpdateGarageRequest $request, Garage $garage): RedirectResponse
    {
        $this->authorizeGarage($request, $garage);
        $garage->update($request->validated());

        return to_route('garages.index')->with('status', __('garages.updated'));
    }

    public function destroy(Request $request, Garage $garage): RedirectResponse
    {
        $this->authorizeGarage($request, $garage);
        $garage->delete();

        return to_route('garages.index')->with('status', __('garages.deleted'))->with('status_type', 'danger');
    }

    private function authorizeGarage(Request $request, Garage $garage): void
    {
        if ($request->user()->isSuperadmin()) {
            return;
        }

        abort_unless($garage->fleet_id !== null && $garage->fleet_id === $request->user()->fleet_id, 403);
    }
}
