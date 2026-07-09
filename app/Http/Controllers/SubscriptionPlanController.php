<?php

namespace App\Http\Controllers;

use App\Models\VehicleSubscriptionFeature;
use App\Models\VehicleSubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage-subscriptions');

        $plans = VehicleSubscriptionPlan::query()
            ->ordered()
            ->get();

        $features = VehicleSubscriptionFeature::query()
            ->ordered()
            ->get();

        return view('subscriptions.index', [
            'plans' => $plans,
            'features' => $features,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('manage-subscriptions');

        $plans = VehicleSubscriptionPlan::query()->get()->keyBy('code');
        $features = VehicleSubscriptionFeature::query()->get()->keyBy('code');
        $allowedFeatures = $features->keys()->all();

        $validated = $request->validate([
            'plans' => ['nullable', 'array'],
            'plans.*.name' => ['required', 'string', 'max:80'],
            'plans.*.description' => ['nullable', 'string', 'max:180'],
            'plans.*.is_active' => ['nullable', 'boolean'],
            'plans.*.features' => ['nullable', 'array'],
            'plans.*.features.*' => ['string', Rule::in($allowedFeatures)],
            'new_plan.name' => ['nullable', 'string', 'max:80'],
            'new_plan.code' => ['nullable', 'alpha_dash:ascii', 'max:80', Rule::unique('vehicle_subscription_plans', 'code')],
            'new_plan.description' => ['nullable', 'string', 'max:180'],
            'new_plan.color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'new_plan.features' => ['nullable', 'array'],
            'new_plan.features.*' => ['string', Rule::in($allowedFeatures)],
        ]);

        DB::transaction(function () use ($request, $validated, $plans, $allowedFeatures): void {
            foreach ($validated['plans'] ?? [] as $code => $planData) {
                $plan = $plans->get((string) $code);

                if (! $plan) {
                    continue;
                }

                $plan->update([
                    'name' => $planData['name'],
                    'description' => $planData['description'] ?? null,
                    'features' => array_values(array_intersect($planData['features'] ?? [], $allowedFeatures)),
                    'is_active' => $request->boolean("plans.$code.is_active"),
                ]);
            }

            $newPlan = $validated['new_plan'] ?? [];

            if (filled($newPlan['name'] ?? null)) {
                VehicleSubscriptionPlan::query()->create([
                    'code' => $this->uniquePlanCode($newPlan['code'] ?? null, $newPlan['name']),
                    'name' => $newPlan['name'],
                    'description' => $newPlan['description'] ?? null,
                    'color' => $newPlan['color'] ?? $this->nextPlanColor(),
                    'features' => array_values(array_intersect($newPlan['features'] ?? [], $allowedFeatures)),
                    'sort_order' => ((int) VehicleSubscriptionPlan::query()->max('sort_order')) + 10,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()
            ->route('subscriptions.index')
            ->with('status', __('subscriptions.updated'));
    }

    private function uniquePlanCode(?string $code, string $name): string
    {
        return $this->uniqueCode($code ?: $name, VehicleSubscriptionPlan::class);
    }

    /**
     * @param class-string<VehicleSubscriptionPlan> $model
     */
    private function uniqueCode(string $value, string $model): string
    {
        $baseCode = Str::of($value)
            ->ascii()
            ->lower()
            ->slug('_')
            ->limit(70, '')
            ->value();

        $baseCode = $baseCode !== '' ? $baseCode : 'item';
        $code = $baseCode;
        $suffix = 2;

        while ($model::query()->where('code', $code)->exists()) {
            $code = $baseCode.'_'.$suffix;
            $suffix++;
        }

        return $code;
    }

    private function nextPlanColor(): string
    {
        $colors = ['#171064', '#137f86', '#1f4ed8', '#7c3aed', '#f59e0b', '#0f766e'];
        $plansCount = VehicleSubscriptionPlan::query()->count();

        return $colors[$plansCount % count($colors)];
    }
}
