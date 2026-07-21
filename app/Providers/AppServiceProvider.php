<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\ApplicationSetting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ApplicationSetting::class, fn (): ApplicationSetting => ApplicationSetting::query()->firstOrFail());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-platform', fn (User $user): bool => $user->isSuperadmin());

        Gate::define('manage-subscriptions', fn (User $user): bool => $user->isSuperadmin());

        Gate::define('manage-users', fn (User $user): bool => $user->isSuperadmin() || $user->isAdmin());

        Gate::define('view-subscription', function (User $user, Subscription|int|null $subscription): bool {
            return $user->canAccessSubscription($subscription);
        });

        Gate::define('manage-subscription-users', function (User $user, Subscription|int|null $subscription): bool {
            return $user->isSuperadmin()
                || ($user->isAdmin() && $user->canAccessSubscription($subscription));
        });

        View::composer('partials.topbar-actions', function ($view): void {
            $user = Auth::user();

            $view->with('newAlertsCount', $user
                ? Alert::query()->visibleTo($user)->where('status', 'new')->count()
                : 0);
        });

        View::composer('*', function ($view): void {
            $view->with('applicationSettings', app(ApplicationSetting::class));
        });
    }
}
