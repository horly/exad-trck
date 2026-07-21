<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventStaleAuthenticationPages
{
    /**
     * @var list<string>
     */
    private const AUTH_PAGE_ROUTES = [
        'login',
        'password.request',
        'password.reset',
        'two-factor.login',
        'password.confirm',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && in_array($request->route()?->getName(), self::AUTH_PAGE_ROUTES, true)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
