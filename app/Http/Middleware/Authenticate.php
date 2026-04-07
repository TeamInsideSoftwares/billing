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
        if (!auth()->guard($guards[0] ?? null)->check()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
