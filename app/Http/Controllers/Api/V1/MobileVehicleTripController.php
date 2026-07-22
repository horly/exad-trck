<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MobileVehicleTripsRequest;
use App\Models\Vehicle;
use App\Services\DeviceTripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class MobileVehicleTripController extends Controller
{
    public function __invoke(
        MobileVehicleTripsRequest $request,
        int $vehicle,
        DeviceTripService $tripService,
    ): JsonResponse {
        $model = Vehicle::query()
            ->visibleTo($request->user())
            ->with('device')
            ->findOrFail($vehicle);
        [$from, $to, $period] = $this->period($request);

        if ($model->device === null) {
            return response()->json([
                'data' => $this->emptyPayload($model, $period, $from, $to),
            ]);
        }

        $payload = $tripService->build($model->device, $from, $to);

        return response()->json([
            'data' => [
                'vehicle' => $this->vehicleData($model),
                'tracking_configured' => true,
                'period' => $this->periodData($period, $from, $to),
                'summary' => [
                    'count' => count($payload['trips']),
                    'distance_km' => $payload['total_distance_km'],
                    'duration_seconds' => $payload['total_duration_seconds'],
                ],
                'trips' => $payload['trips'],
                'geojson' => $payload['geojson'],
            ],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function period(MobileVehicleTripsRequest $request): array
    {
        $period = (string) $request->validated('period', 'today');
        $now = now();

        return match ($period) {
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), $period],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), $period],
            'current_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), $period],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
                $period,
            ],
            'custom' => $this->customPeriod($request),
            default => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'today'],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function customPeriod(MobileVehicleTripsRequest $request): array
    {
        $from = Carbon::parse((string) $request->validated('start_date'))->startOfDay();
        $to = Carbon::parse((string) $request->validated('end_date'))->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to, 'custom'];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(Vehicle $vehicle, string $period, Carbon $from, Carbon $to): array
    {
        return [
            'vehicle' => $this->vehicleData($vehicle),
            'tracking_configured' => false,
            'period' => $this->periodData($period, $from, $to),
            'summary' => ['count' => 0, 'distance_km' => 0.0, 'duration_seconds' => 0],
            'trips' => [],
            'geojson' => ['type' => 'FeatureCollection', 'features' => []],
        ];
    }

    /**
     * @return array{id: int, name: string, registration_number: string}
     */
    private function vehicleData(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'name' => $vehicle->name,
            'registration_number' => $vehicle->registration_number,
        ];
    }

    /**
     * @return array{key: string, from: string, to: string}
     */
    private function periodData(string $period, Carbon $from, Carbon $to): array
    {
        return [
            'key' => $period,
            'from' => $from->toISOString(),
            'to' => $to->toISOString(),
        ];
    }
}
