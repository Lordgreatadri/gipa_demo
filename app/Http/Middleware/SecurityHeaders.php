<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies a baseline set of hardening response headers on every web response,
 * including a strict Content-Security-Policy. A fresh per-request nonce is
 * generated and handed to Vite so first-party script/style tags carry it,
 * allowing script-src to drop 'unsafe-inline' entirely.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(24);

        // Vite stamps this nonce onto the script/style tags it renders so the
        // strict policy below can authorise them without 'unsafe-inline'.
        Vite::useCspNonce($nonce);

        $response = $next($request);

        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(self), browsing-topics=()',
            'Content-Security-Policy' => $this->contentSecurityPolicy($nonce),
        ];

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        // Only advertise HSTS once the connection is already encrypted.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Build the Content-Security-Policy. Scripts are locked to first-party code
     * and the request nonce; styles keep 'unsafe-inline' because Leaflet and
     * Chart.js set element styles at runtime. Map tiles and the self-hosted
     * font service are the only third-party origins permitted.
     */
    private function contentSecurityPolicy(string $nonce): string
    {
        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src 'self' https://fonts.bunny.net",
            "img-src 'self' data: blob: https://*.tile.openstreetmap.org",
            "connect-src 'self'",
            "manifest-src 'self'",
            "worker-src 'self'",
        ]);
    }
}
