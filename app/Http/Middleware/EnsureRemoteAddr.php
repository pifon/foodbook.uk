<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure REMOTE_ADDR is never null/empty so Symfony's IpUtils (used by TrustProxies
 * and isSecure()) does not receive null. Nginx/FastCGI may omit it in some setups.
 */
class EnsureRemoteAddr
{
    public function handle(Request $request, Closure $next): Response
    {
        $addr = $request->server->get('REMOTE_ADDR');
        if ($addr === null || $addr === '') {
            $request->server->set('REMOTE_ADDR', '127.0.0.1');
        }

        return $next($request);
    }
}
