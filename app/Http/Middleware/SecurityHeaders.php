<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Clickjacking protection — the login page sits in front of real
        // financial data and Super Admin sessions, so it should never be
        // frameable by another origin.
        $response->headers->set('X-Frame-Options', 'DENY');

        // Legacy MIME-sniffing protection; harmless to set even on modern
        // browsers that already default-deny sniffing.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Only send HSTS over an actual HTTPS response — sending it on a
        // plain HTTP response (e.g. local dev) can lock browsers into
        // HTTPS-only for the domain even where TLS isn't configured yet.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // A real CSP, not just upgrade-insecure-requests (which doesn't
        // restrict anything — it just rewrites http:// asset URLs to
        // https://). This is intentionally permissive on script-src/style-src
        // because the app relies on Alpine.js inline directives and inline
        // <style> blocks throughout — tightening those further would require
        // migrating to nonces/hashes, which is a larger follow-up task.
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://unpkg.com https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https:",
            "connect-src 'self' https://psgc.cloud https://nominatim.openstreetmap.org https://api.paymongo.com https://api.qrserver.com",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests",
        ]));

        return $response;
    }
}