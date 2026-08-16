<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class RequireDashboardToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('app.dashboard_token');

        if ($expected === '') {
            return response()->json([
                'success' => false,
                'message' => 'El acceso no está configurado. Define DASHBOARD_ACCESS_TOKEN en .env.',
            ], 503);
        }

        $provided = $request->bearerToken()
            ?: $request->header('X-Dashboard-Token')
            ?: $request->cookie('dashboard_token')
            ?: $request->query('token');

        if (!is_string($provided) || !hash_equals($expected, $provided)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de acceso inválido o ausente.',
                ], 401);
            }

            return response('Acceso no autorizado.', 401);
        }

        if (!$request->cookie('dashboard_token')) {
            Cookie::queue(cookie(
                'dashboard_token',
                $expected,
                60 * 24 * 30,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax'
            ));

            if ($request->query('token') !== null && $request->routeIs('dashboard')) {
                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}
