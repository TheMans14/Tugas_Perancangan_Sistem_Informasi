<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class IpMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$ips)
    {
        $ip = $request->ip();

        // The local web dashboard can reach this development server via loopback.
        if (app()->environment('local') && in_array($ip, ['127.0.0.1', '::1'], true)) {
            return $next($request);
        }

        foreach ($ips as $allowedIp) {
            if (Str::is($allowedIp, $ip)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
