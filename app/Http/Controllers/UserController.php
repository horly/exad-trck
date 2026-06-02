<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserLoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
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
        $assignableRoles = [
            UserRole::User,
            UserRole::Admin,
        ];

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('case when role = ? then 0 else 1 end', [UserRole::Superadmin->value])
            ->when($sort !== null, function ($query) use ($sortableColumns, $sort, $direction): void {
                $query->orderBy($sortableColumns[$sort], $direction)->orderByDesc('created_at')->orderByDesc('id');
            }, function ($query): void {
                $query->orderByDesc('id');
            })
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
            'roles' => $assignableRoles,
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
            'role' => ['required', Rule::in([UserRole::User->value, UserRole::Admin->value])],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'status' => 'active',
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'permissions' => [],
        ]);

        return redirect()
            ->route('users.index')
            ->with('status', __('users.created'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isSuperadmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
            'role' => ['required', Rule::in([UserRole::User->value, UserRole::Admin->value])],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('status', __('users.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->isSuperadmin() && User::query()->superadmins()->count() <= 1, 403);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', __('users.deleted'))
            ->with('status_type', 'danger');
    }
}
