<?php

namespace Modules\FluidPay\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\FluidPay\Support\Config;

class AllowFluidPayCsp
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $baseUrl = Config::baseUrl();

        $policy = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$baseUrl} https://cdn.3dsintegrator.com",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: {$baseUrl}",
            "connect-src 'self' {$baseUrl}",
            "frame-src {$baseUrl}",
        ]);

        $response->headers->remove('Content-Security-Policy');
        $response->headers->remove('Content-Security-Policy-Report-Only');

        $response->headers->set('Content-Security-Policy', $policy);

        return $response;
    }
}
