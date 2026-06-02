<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'canManageFleets' => $request->user()->isSuperadmin() || $request->user()->isAdmin(),
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
        $data['subscription_id'] = null;

        $fleet = Fleet::query()->create($data);
        $this->syncFleetManager($request, $fleet);

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

        $fleet->update($this->validatedFleetData($request, $fleet));
        $this->syncFleetManager($request, $fleet);

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

        abort_unless($user->isSuperadmin() || $user->isAdmin(), 403);

        if ($fleet !== null) {
            if ($user->isSuperadmin()) {
                return;
            }

            $isManager = $fleet->users()
                ->whereKey($user->id)
                ->wherePivot('permission', 'manager')
                ->exists();

            $hasLegacyAccess = $fleet->users()->doesntExist()
                && $user->canAccessSubscription($fleet->subscription_id);

            abort_unless($isManager || $hasLegacyAccess, 403);
        }
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
                Rule::requiredIf($request->user()->isSuperadmin()),
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($request): void {
                    $query
                        ->where('role', UserRole::Admin->value)
                        ->where('status', 'active');

                    if (! $request->user()->isSuperadmin()) {
                        $query->where('subscription_id', $request->user()->subscription_id);
                    }
                }),
            ],
        ]);
    }

    private function assignableAdmins(Request $request)
    {
        if (! $request->user()->isSuperadmin() && ! $request->user()->isAdmin()) {
            return collect();
        }

        return User::query()
            ->active()
            ->where('role', UserRole::Admin->value)
            ->when(! $request->user()->isSuperadmin(), fn ($query) => $query->forSubscription($request->user()->subscription_id))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
    }

    private function syncFleetManager(Request $request, Fleet $fleet): void
    {
        $managerId = $request->user()->isSuperadmin()
            ? (int) $request->input('admin_id')
            : $request->user()->id;

        $manager = User::query()
            ->active()
            ->where('role', UserRole::Admin->value)
            ->when(! $request->user()->isSuperadmin(), fn ($query) => $query->forSubscription($request->user()->subscription_id))
            ->whereKey($managerId)
            ->firstOrFail(['id']);

        $fleet->users()->sync([
            $manager->id => ['permission' => 'manager'],
        ]);
    }
}
