<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('api_token')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'error' => 'Please log in to continue.',
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Please log in to continue.');
        }

        return $next($request);
    }
}
