<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            $otherPermissions = array_filter($user->permissions ?? [], fn ($p) => $p !== 'team_work.view');
            $hasBilling = count($otherPermissions) > 0 || ($user->role && $user->role->name === 'Admin');

            if (! $hasBilling) {
                if ($user->hasPermission('team_work.view')) {
                    return redirect()->away(config('app.team_url').'/dashboard');
                }

                abort(403, 'You do not have access to this application.');
            }
        }

        return $next($request);
    }
}
