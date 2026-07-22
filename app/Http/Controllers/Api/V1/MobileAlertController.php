<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MobileAlertResource;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class MobileAlertController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['new', 'acknowledged', 'resolved'])],
            'severity' => ['nullable', Rule::in(['info', 'low', 'medium', 'high', 'critical'])],
            'vehicle_id' => ['nullable', 'integer'],
            'after_id' => ['nullable', 'integer', 'min:0'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $alerts = Alert::query()
            ->visibleTo($request->user())
            ->with(['fleet:id,name,code', 'vehicle:id,name,registration_number'])
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['severity']), fn ($query) => $query->where('severity', $filters['severity']))
            ->when(isset($filters['vehicle_id']), fn ($query) => $query->where('vehicle_id', $filters['vehicle_id']))
            ->when(isset($filters['after_id']), fn ($query) => $query->where('id', '>', $filters['after_id']))
            ->orderByRaw("CASE WHEN status IN ('acknowledged', 'resolved') THEN 1 ELSE 0 END")
            ->latest('occurred_at')
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->withQueryString();

        return MobileAlertResource::collection($alerts);
    }
}
