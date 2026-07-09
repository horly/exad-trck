<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('subscriptions.title') }} - EXAD Tracking</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}?v=20260528-compact-ui">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v=20260709-subscription-check-size">
</head>
<body class="app-font-manrope dashboard-body">
    <div class="dashboard-shell">
        @include('partials.sidebar', ['active' => 'subscriptions'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                @include('partials.sidebar-toggle')
                <div>
                    <p class="eyebrow mb-1">{{ __('subscriptions.eyebrow') }}</p>
                    <h1>{{ __('subscriptions.title') }}</h1>
                    <p class="dashboard-subtitle">{{ __('subscriptions.subtitle') }}</p>
                </div>

                @include('partials.topbar-actions')
            </header>

            <div class="users-page-actions subscription-page-actions">
                <button type="button" class="btn btn-primary users-primary-button" data-bs-toggle="modal" data-bs-target="#subscriptionPlanModal">
                    <i class="fa-solid fa-plus"></i>
                    <span>{{ __('subscriptions.add_plan') }}</span>
                </button>
            </div>

            <form method="POST" action="{{ route('subscriptions.update') }}" class="subscriptions-page" data-loading-form data-loading-text="{{ __('subscriptions.processing') }}">
                @csrf
                @method('PATCH')

                <section class="subscription-plan-grid" aria-label="{{ __('subscriptions.plans_overview') }}">
                    @foreach ($plans as $plan)
                        <article class="subscription-plan-card" style="--plan-color: {{ $plan->color ?: '#171064' }}">
                            <div class="subscription-plan-icon">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <div class="subscription-plan-copy">
                                <label for="plan_name_{{ $plan->code }}" class="subscription-plan-label">{{ __('subscriptions.plan_name') }}</label>
                                <input
                                    id="plan_name_{{ $plan->code }}"
                                    name="plans[{{ $plan->code }}][name]"
                                    class="form-control @error('plans.'.$plan->code.'.name') is-invalid @enderror"
                                    value="{{ old('plans.'.$plan->code.'.name', $plan->name) }}"
                                    required
                                >
                                @error('plans.'.$plan->code.'.name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="subscription-plan-state">
                                <input type="hidden" name="plans[{{ $plan->code }}][is_active]" value="0">
                                <label class="feature-switch">
                                    <input type="checkbox" name="plans[{{ $plan->code }}][is_active]" value="1" @checked(old('plans.'.$plan->code.'.is_active', $plan->is_active))>
                                    <span>{{ __('subscriptions.active') }}</span>
                                </label>
                            </div>
                            <div class="subscription-plan-description">
                                <label for="plan_description_{{ $plan->code }}" class="subscription-plan-label">{{ __('subscriptions.description') }}</label>
                                <textarea
                                    id="plan_description_{{ $plan->code }}"
                                    name="plans[{{ $plan->code }}][description]"
                                    class="form-control"
                                    rows="2"
                                >{{ old('plans.'.$plan->code.'.description', $plan->description) }}</textarea>
                            </div>
                        </article>
                    @endforeach
                </section>

                <section class="subscription-matrix-card">
                    <div class="subscription-section-heading">
                        <div>
                            <p class="eyebrow mb-1">{{ __('subscriptions.features_eyebrow') }}</p>
                            <h2>{{ __('subscriptions.features_title') }}</h2>
                        </div>
                    </div>

                    <div class="subscription-matrix" style="--plans-count: {{ max($plans->count(), 1) }}">
                        <div class="subscription-matrix-row subscription-matrix-head">
                            <div>{{ __('subscriptions.feature') }}</div>
                            @foreach ($plans as $plan)
                                <div>
                                    <span class="plan-column-pill" style="--plan-color: {{ $plan->color ?: '#171064' }}">{{ $plan->name }}</span>
                                </div>
                            @endforeach
                        </div>

                        @foreach ($features as $feature)
                            <div class="subscription-matrix-row {{ $feature->is_active ? '' : 'is-muted' }}">
                                <div class="subscription-feature-copy">
                                    <strong>{{ $feature->name }}</strong>
                                    <span>{{ $feature->description }}</span>
                                </div>
                                @foreach ($plans as $plan)
                                    @php($enabledFeatures = old('plans.'.$plan->code.'.features', $plan->features ?? []))
                                    <label class="feature-check" title="{{ __('subscriptions.toggle_feature', ['feature' => $feature->name, 'plan' => $plan->name]) }}">
                                        <input
                                            type="checkbox"
                                            name="plans[{{ $plan->code }}][features][]"
                                            value="{{ $feature->code }}"
                                            @checked(in_array($feature->code, $enabledFeatures, true))
                                        >
                                        <span>
                                            <i class="fa-solid fa-check"></i>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <div class="subscription-matrix-actions">
                        <button type="submit" class="btn btn-primary users-primary-button" data-loading-button>
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>{{ __('subscriptions.save') }}</span>
                        </button>
                    </div>
                </section>
            </form>
        </main>
    </div>

    <div class="modal fade users-modal" id="subscriptionPlanModal" tabindex="-1" aria-labelledby="subscriptionPlanModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered users-modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('subscriptions.update') }}" data-loading-form data-loading-text="{{ __('subscriptions.processing') }}">
                    @csrf
                    @method('PATCH')

                    <div class="modal-header">
                        <h2 class="modal-title" id="subscriptionPlanModalTitle">{{ __('subscriptions.create_plan_title') }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('subscriptions.cancel') }}"></button>
                    </div>

                    <div class="modal-body">
                        <div class="users-form-grid">
                            <div>
                                <label for="new_plan_name" class="form-label">{{ __('subscriptions.plan_name') }} *</label>
                                <input
                                    id="new_plan_name"
                                    name="new_plan[name]"
                                    class="form-control @error('new_plan.name') is-invalid @enderror"
                                    value="{{ old('new_plan.name') }}"
                                    placeholder="{{ __('subscriptions.new_plan_name_placeholder') }}"
                                    required
                                >
                                @error('new_plan.name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="subscription-color-field subscription-modal-color-field">
                                <label for="new_plan_color" class="form-label">{{ __('subscriptions.color') }}</label>
                                <input id="new_plan_color" type="color" name="new_plan[color]" value="{{ old('new_plan.color', '#1f4ed8') }}">
                            </div>

                            <div class="grid-full">
                                <label for="new_plan_description" class="form-label">{{ __('subscriptions.description') }}</label>
                                <textarea
                                    id="new_plan_description"
                                    name="new_plan[description]"
                                    class="form-control"
                                    rows="3"
                                    placeholder="{{ __('subscriptions.new_plan_description_placeholder') }}"
                                >{{ old('new_plan.description') }}</textarea>
                            </div>

                            <div class="grid-full subscription-new-plan-features subscription-modal-features">
                                <strong>{{ __('subscriptions.initial_features') }}</strong>
                                <div>
                                    @foreach ($features as $feature)
                                        <label>
                                            <input type="checkbox" name="new_plan[features][]" value="{{ $feature->code }}" @checked(in_array($feature->code, old('new_plan.features', []), true))>
                                            <span>{{ $feature->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn users-cancel-button" data-bs-dismiss="modal">{{ __('subscriptions.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" data-loading-button>{{ __('subscriptions.create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="app-toast app-toast-success" role="status" aria-live="polite" data-app-toast>
            <span class="app-toast-icon" aria-hidden="true">
                <i class="fa-solid fa-check"></i>
            </span>
            <span class="app-toast-message">{{ session('status') }}</span>
            <button type="button" class="app-toast-close" aria-label="{{ __('subscriptions.close_notification') }}" data-app-toast-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
            <span class="app-toast-progress" aria-hidden="true"></span>
        </div>
    @endif

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/dashboard-sidebar.js') }}?v=20260626-responsive-sidebar-default"></script>
    <script src="{{ asset('js/dashboard-controls.js') }}?v=20260529-shared-controls"></script>
    <script src="{{ asset('js/form-loading.js') }}?v=20260529-form-loading"></script>
    @include('partials.realtime-alerts')
    <script>
        @if ($errors->has('new_plan.name') || $errors->has('new_plan.code') || $errors->has('new_plan.description') || $errors->has('new_plan.color') || $errors->has('new_plan.features'))
            const subscriptionPlanModal = document.getElementById('subscriptionPlanModal');
            if (subscriptionPlanModal && window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(subscriptionPlanModal).show();
            }
        @endif

        const subscriptionToast = document.querySelector('[data-app-toast]');
        if (subscriptionToast) {
            const hideToast = () => subscriptionToast.classList.add('is-hiding');
            subscriptionToast.querySelector('[data-app-toast-close]')?.addEventListener('click', hideToast);
            setTimeout(hideToast, 5200);
        }
    </script>
</body>
</html>
