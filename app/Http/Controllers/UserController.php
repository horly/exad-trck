<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Fleet;
use App\Models\User;
use App\Models\UserLoginHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor->isSuperadmin() || $actor->isAdmin(), 403);

        $search = trim((string) $request->query('search', ''));
        $isDatatableRequest = $request->ajax();
        $sortableColumns = [
            'id' => 'users.id',
            'name' => 'name',
            'email' => 'email',
            'role' => 'role',
            'phone' => 'phone',
        ];
        $sort = $isDatatableRequest && array_key_exists((string) $request->query('sort'), $sortableColumns)
            ? (string) $request->query('sort')
            : null;
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $users = User::query()
            ->with('fleet:id,name,code')
            ->when($actor->isAdmin(), fn ($query) => $query
                ->where('fleet_id', $actor->fleet_id)
                ->where('role', UserRole::User->value))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->when($actor->isSuperadmin(), fn ($query) => $query
                ->orderByRaw('case when role = ? then 0 else 1 end', [UserRole::Superadmin->value]))
            ->when($sort !== null, function ($query) use ($sortableColumns, $sort, $direction): void {
                $query->orderBy($sortableColumns[$sort], $direction)->orderByDesc('created_at')->orderByDesc('id');
            }, fn ($query) => $query->orderByDesc('id'))
            ->paginate(5)
            ->withQueryString();

        $loginHistories = UserLoginHistory::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->latest('logged_in_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($histories) => $histories->take(12)->values()->map(fn (UserLoginHistory $history): array => [
                'device' => $history->device,
                'ip' => $history->ip_address ?: '-',
                'date' => $history->logged_in_at->format('Y-m-d H:i:s'),
            ]));

        $viewData = [
            'users' => $users,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'roles' => $actor->isSuperadmin() ? [UserRole::User, UserRole::Admin] : [UserRole::User],
            'fleets' => $actor->isSuperadmin()
                ? Fleet::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'code'])
                : collect([$actor->fleet])->filter(),
            'clientPermissions' => User::CLIENT_PERMISSIONS,
            'isPlatformUserManagement' => $actor->isSuperadmin(),
            'loginHistories' => $loginHistories,
        ];

        if ($isDatatableRequest) {
            return response()->json([
                'html' => view('users.partials.table', $viewData)->render(),
                'loginHistories' => $loginHistories,
            ]);
        }

        return view('users.index', $viewData);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor->isSuperadmin() || $actor->isAdmin(), 403);

        $data = $this->validatedData($request);
        $role = $actor->isSuperadmin() ? UserRole::from($data['role']) : UserRole::User;
        $fleet = $this->targetFleet($request, $data);
        $permissions = $role === UserRole::User ? ($data['permissions'] ?? []) : [];

        DB::transaction(function () use ($actor, $data, $role, $fleet, $permissions): void {
            $user = User::query()->create([
                'fleet_id' => $fleet->id,
                'created_by' => $actor->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $role,
                'status' => 'active',
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'permissions' => $permissions,
            ]);

            $user->fleets()->sync([$fleet->id => [
                'permission' => $role === UserRole::Admin ? 'manager' : 'viewer',
            ]]);
        });

        return to_route('users.index')->with('status', __('users.created'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->authorizeManagedUser($actor, $user);

        $data = $this->validatedData($request, $user);
        $role = $actor->isSuperadmin() ? UserRole::from($data['role']) : UserRole::User;
        $fleet = $this->targetFleet($request, $data);

        DB::transaction(function () use ($data, $role, $fleet, $user): void {
            $user->fill([
                'fleet_id' => $fleet->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $role,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'permissions' => $role === UserRole::User ? ($data['permissions'] ?? []) : [],
            ]);

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();
            $user->fleets()->sync([$fleet->id => [
                'permission' => $role === UserRole::Admin ? 'manager' : 'viewer',
            ]]);
        });

        return to_route('users.index')->with('status', __('users.updated'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManagedUser($request->user(), $user);
        $user->delete();

        return to_route('users.index')
            ->with('status', __('users.deleted'))
            ->with('status_type', 'danger');
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request, ?User $user = null): array
    {
        $isSuperadmin = $request->user()->isSuperadmin();

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
            'role' => [$isSuperadmin ? 'required' : 'nullable', Rule::in([UserRole::User->value, UserRole::Admin->value])],
            'fleet_id' => [$isSuperadmin ? 'required' : 'nullable', 'integer', Rule::exists('fleets', 'id')->where('status', 'active')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(User::CLIENT_PERMISSIONS)],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /** @param array<string, mixed> $data */
    private function targetFleet(Request $request, array $data): Fleet
    {
        $fleetId = $request->user()->isSuperadmin() ? $data['fleet_id'] : $request->user()->fleet_id;

        abort_if($fleetId === null, 422, __('users.fleet_required'));

        return Fleet::query()->where('status', 'active')->findOrFail($fleetId);
    }

    private function authorizeManagedUser(User $actor, User $user): void
    {
        abort_if($user->isSuperadmin(), 403);

        if ($actor->isSuperadmin()) {
            return;
        }

        abort_unless(
            $actor->isAdmin()
            && $user->isSimpleUser()
            && $actor->fleet_id !== null
            && $user->fleet_id === $actor->fleet_id,
            403,
        );
    }
}
