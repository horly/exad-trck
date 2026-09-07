<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\Fleet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('view-departments');

        $user = $request->user();
        $canManageDepartments = Gate::allows('manage-departments')
            && ! $request->attributes->getBoolean('client_preview');
        $search = trim((string) $request->query('search', ''));
        $isAjax = $request->ajax();
        $columns = [
            'id' => 'departments.id',
            'name' => 'departments.name',
            'code' => 'departments.code',
            'fleet' => 'fleet_name',
            'drivers' => 'drivers_count',
            'status' => 'departments.status',
        ];
        $sort = $isAjax && isset($columns[(string) $request->query('sort')])
            ? (string) $request->query('sort')
            : null;
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $departments = Department::query()
            ->visibleTo($user)
            ->select('departments.*')
            ->leftJoin('fleets', 'fleets.id', '=', 'departments.fleet_id')
            ->addSelect('fleets.name as fleet_name')
            ->with('fleet:id,name,code')
            ->withCount('drivers')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('departments.name', 'like', "%{$search}%")
                        ->orWhere('departments.code', 'like', "%{$search}%")
                        ->orWhere('departments.description', 'like', "%{$search}%")
                        ->orWhere('departments.status', 'like', "%{$search}%")
                        ->orWhere('fleets.name', 'like', "%{$search}%");
                });
            })
            ->when($sort, fn ($query) => $query->orderBy($columns[$sort], $direction)->orderByDesc('departments.id'),
                fn ($query) => $query->latest('departments.created_at')->latest('departments.id'))
            ->paginate(5)
            ->withQueryString();

        $data = [
            'departments' => $departments,
            'fleets' => $canManageDepartments
                ? Fleet::query()->visibleTo($user)->orderBy('name')->get(['id', 'name', 'code'])
                : collect(),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'canManageDepartments' => $canManageDepartments,
        ];

        if ($isAjax) {
            return response()->json(['html' => view('departments.partials.table', $data)->render()]);
        }

        return view('departments.index', $data);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::query()->create($request->validated());

        return to_route('departments.index')->with('status', __('departments.created'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return to_route('departments.index')->with('status', __('departments.updated'));
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('delete-department', $department);

        $deleted = DB::transaction(function () use ($department): bool {
            $lockedDepartment = Department::query()->lockForUpdate()->findOrFail($department->id);

            if ($lockedDepartment->drivers()->exists()) {
                return false;
            }

            return (bool) $lockedDepartment->delete();
        });

        if (! $deleted) {
            return to_route('departments.index')
                ->with('status', __('departments.delete_blocked'))
                ->with('status_type', 'danger');
        }

        return to_route('departments.index')
            ->with('status', __('departments.deleted'))
            ->with('status_type', 'danger');
    }
}
