<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FleetController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $isDatatableRequest = $request->ajax();
        $sortableColumns = [
            'id' => 'fleets.id',
            'name' => 'name',
            'code' => 'code',
            'status' => 'status',
            'vehicles' => 'vehicles_count',
        ];
        $sort = $isDatatableRequest && array_key_exists((string) $request->query('sort'), $sortableColumns)
            ? (string) $request->query('sort')
            : null;
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $fleets = Fleet::query()
            ->visibleTo($request->user())
            ->with(['users:id,name,email,role'])
            ->withCount([
                'vehicles',
                'vehicles as premium_vehicles_count' => fn ($query) => $query->where('subscription_plan', 'premium'),
                'vehicles as standard_vehicles_count' => fn ($query) => $query->where('subscription_plan', 'standard'),
                'vehicles as basic_vehicles_count' => fn ($query) => $query->where('subscription_plan', 'basic'),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('users', function ($query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($sort !== null, function ($query) use ($sortableColumns, $sort, $direction): void {
                $query->orderBy($sortableColumns[$sort], $direction)->orderByDesc('created_at')->orderByDesc('id');
            }, function ($query): void {
                $query->latest();
            })
            ->paginate(5)
            ->withQueryString();

        $viewData = [
            'fleets' => $fleets,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'canManageFleets' => $request->user()->isSuperadmin(),
            'assignableAdmins' => $this->assignableAdmins($request),
        ];

        if ($isDatatableRequest) {
            return response()->json([
                'html' => view('fleets.partials.table', $viewData)->render(),
            ]);
        }

        return view('fleets.index', $viewData);
    }

    public function create(Request $request): RedirectResponse
    {
        $this->authorizeFleetManagement($request);

        return redirect()->route('fleets.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeFleetManagement($request);

        $data = $this->validatedFleetData($request);
        $managerId = isset($data['admin_id']) ? (int) $data['admin_id'] : null;
        unset($data['admin_id']);
        $data['subscription_id'] = null;

        DB::transaction(function () use ($data, $managerId): void {
            $fleet = Fleet::query()->create($data);
            $this->syncFleetManager($fleet, $managerId);
        });

        return redirect()
            ->route('fleets.index')
            ->with('status', __('fleets.created'));
    }

    public function edit(Request $request, Fleet $fleet): RedirectResponse
    {
        $this->authorizeFleetManagement($request, $fleet);

        return redirect()->route('fleets.index');
    }

    public function update(Request $request, Fleet $fleet): RedirectResponse
    {
        $this->authorizeFleetManagement($request, $fleet);

        $data = $this->validatedFleetData($request, $fleet);
        $managerId = isset($data['admin_id']) ? (int) $data['admin_id'] : null;
        unset($data['admin_id']);

        DB::transaction(function () use ($data, $fleet, $managerId): void {
            $fleet->update($data);
            $this->syncFleetManager($fleet, $managerId);
        });

        return redirect()
            ->route('fleets.index')
            ->with('status', __('fleets.updated'));
    }

    public function destroy(Request $request, Fleet $fleet): RedirectResponse
    {
        $this->authorizeFleetManagement($request, $fleet);

        $fleet->delete();

        return redirect()
            ->route('fleets.index')
            ->with('status', __('fleets.deleted'))
            ->with('status_type', 'danger');
    }

    private function authorizeFleetManagement(Request $request, ?Fleet $fleet = null): void
    {
        $user = $request->user();

        abort_unless($user->isSuperadmin(), 403);
    }

    /**
     * @return array{name: string, code: string, description?: string|null, status: string, admin_id?: int|null}
     */
    private function validatedFleetData(Request $request, ?Fleet $fleet = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('fleets')->ignore($fleet),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'admin_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($fleet): void {
                    $query
                        ->where('role', UserRole::Admin->value)
                        ->where('status', 'active')
                        ->where(function ($query) use ($fleet): void {
                            $query->whereNull('fleet_id');

                            if ($fleet !== null) {
                                $query->orWhere('fleet_id', $fleet->id);
                            }
                        });
                }),
            ],
        ]);
    }

    private function assignableAdmins(Request $request)
    {
        if (! $request->user()->isSuperadmin()) {
            return collect();
        }

        return User::query()
            ->active()
            ->where('role', UserRole::Admin->value)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'fleet_id']);
    }

    private function syncFleetManager(Fleet $fleet, ?int $managerId): void
    {
        $currentManagerIds = $fleet->managers()->pluck('users.id');

        User::query()
            ->whereIn('id', $currentManagerIds)
            ->where('fleet_id', $fleet->id)
            ->when($managerId !== null, fn ($query) => $query->where('id', '!=', $managerId))
            ->update(['fleet_id' => null, 'subscription_id' => null]);

        $fleet->users()->detach($currentManagerIds->when(
            $managerId !== null,
            fn ($ids) => $ids->reject(fn ($id) => (int) $id === $managerId),
        ));

        if ($managerId === null) {
            return;
        }

        $manager = User::query()
            ->active()
            ->where('role', UserRole::Admin->value)
            ->where(function ($query) use ($fleet): void {
                $query->whereNull('fleet_id')->orWhere('fleet_id', $fleet->id);
            })
            ->whereKey($managerId)
            ->firstOrFail();

        $manager->forceFill([
            'fleet_id' => $fleet->id,
            'subscription_id' => $fleet->subscription_id,
        ])->save();

        $fleet->users()->syncWithoutDetaching([
            $manager->id => ['permission' => 'manager'],
        ]);
    }
}
