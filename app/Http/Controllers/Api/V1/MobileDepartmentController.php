<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\Api\V1\MobileDepartmentResource;
use App\Models\Department;
use App\Models\Fleet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MobileDepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('view-departments');

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'fleet_id' => ['nullable', 'integer', 'exists:fleets,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $user = $request->user();
        $search = trim((string) ($validated['search'] ?? ''));
        $departments = Department::query()
            ->visibleTo($user)
            ->with('fleet:id,name,code')
            ->withCount('drivers')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when(isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when($user->isSuperadmin() && isset($validated['fleet_id']), fn ($query) => $query->where('fleet_id', $validated['fleet_id']))
            ->orderBy('name')
            ->paginate((int) ($validated['per_page'] ?? 50));

        return response()->json([
            'data' => MobileDepartmentResource::collection($departments->items())->resolve($request),
            'meta' => [
                'current_page' => $departments->currentPage(),
                'last_page' => $departments->lastPage(),
                'per_page' => $departments->perPage(),
                'total' => $departments->total(),
            ],
            'management' => [
                'can_manage' => Gate::allows('manage-departments'),
                'can_delete' => $user->isSuperadmin(),
                'fleets' => Gate::allows('manage-departments')
                    ? Fleet::query()->visibleTo($user)->orderBy('name')->get(['id', 'name', 'code'])
                    : [],
            ],
        ]);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::query()->create($request->validated());
        $department->load('fleet:id,name,code')->loadCount('drivers');

        return response()->json([
            'data' => (new MobileDepartmentResource($department))->resolve($request),
            'message' => __('departments.created'),
        ], 201);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());
        $department->load('fleet:id,name,code')->loadCount('drivers');

        return response()->json([
            'data' => (new MobileDepartmentResource($department))->resolve($request),
            'message' => __('departments.updated'),
        ]);
    }

    public function destroy(Request $request, Department $department): JsonResponse
    {
        Gate::forUser($request->user())->authorize('delete-department', $department);

        $deleted = DB::transaction(function () use ($department): bool {
            $locked = Department::query()->lockForUpdate()->findOrFail($department->id);

            return ! $locked->drivers()->exists() && (bool) $locked->delete();
        });

        if (! $deleted) {
            return response()->json([
                'message' => __('departments.delete_blocked'),
                'error' => ['code' => 'DEPARTMENT_IN_USE'],
            ], 409);
        }

        return response()->json(['message' => __('departments.deleted')]);
    }
}
