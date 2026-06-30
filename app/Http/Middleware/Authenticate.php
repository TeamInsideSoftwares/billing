<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (auth()->guard($guard)->check()) {
                auth()->shouldUse($guard);

                return $next($request);
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return redirect()->route('login');
    }
}
