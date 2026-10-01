<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage in routes: ->middleware('role:owner,store_manager')
 * Register the alias in bootstrap/app.php -> withMiddleware():
 *   $middleware->alias(['role' => \App\Http\Middleware\RoleMiddleware::class]);
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || $user->status !== 'active') {
            abort(403, 'Your account is inactive. Contact the owner.');
        }

        if (! empty($roles) && ! in_array($user->role, $roles, true)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
