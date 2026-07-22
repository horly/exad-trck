<?php

use App\Http\Middleware\ApplyClientPreview;
use App\Http\Middleware\EnsureMobileAccessToken;
use App\Http\Middleware\EnsureUserHasClientPermission;
use App\Http\Middleware\EnsureUserIsSuperadmin;
use App\Http\Middleware\PreventStaleAuthenticationPages;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth', 'superadmin']]
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            PreventStaleAuthenticationPages::class,
            ApplyClientPreview::class,
        ]);

        $middleware->alias([
            'superadmin' => EnsureUserIsSuperadmin::class,
            'client.permission' => EnsureUserHasClientPermission::class,
            'mobile.access' => EnsureMobileAccessToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            return redirect()->to(route('login', ['reason' => 'expired']));
        });
    })->create();
