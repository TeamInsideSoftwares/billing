<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperadminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('superadmin_authenticated', false)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please login with superadmin credentials.',
            ]);
        }

        return $next($request);
    }
}
