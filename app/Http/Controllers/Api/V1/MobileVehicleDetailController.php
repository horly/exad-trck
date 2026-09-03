<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MobileVehicleResource;
use App\Models\Vehicle;
use App\Services\MobileVehicleDetailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileVehicleDetailController extends Controller
{
    public function __invoke(
        Request $request,
        int $vehicle,
        MobileVehicleDetailService $detailService,
    ): JsonResponse {
        $model = Vehicle::query()
            ->visibleTo($request->user())
            ->with(['fleet:id,name,code', 'device'])
            ->findOrFail($vehicle);

        return response()->json([
            'data' => [
                ...(new MobileVehicleResource($model))->resolve($request),
                'details' => $detailService->build(
                    $model,
                    includeTrackerIdentity: true,
                    includeDriverIdentifier: $request->user()->isSuperadmin(),
                ),
            ],
        ]);
    }
}
