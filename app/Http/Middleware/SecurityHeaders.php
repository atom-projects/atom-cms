<?php

namespace App\Http\Middleware;

use App\Support\WebsiteSettingRules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $policy = "base-uri 'self'; frame-ancestors 'self'";

        // The client frame URL carries the login ticket, so it may only load allowed origins.
        if ($request->routeIs('nitro-client')) {
            $policy .= "; frame-src 'self' " . implode(' ', WebsiteSettingRules::clientOrigins());
        }

        $response->headers->set('Content-Security-Policy', $policy);
        $response->headers->set('Permissions-Policy', 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
