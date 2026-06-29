<?php

namespace App\Http\Middleware;

use App\Support\Subdirectory;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FixSubdirectoryRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasSession() && $request->session()->has('url.intended')) {
            $request->session()->put(
                'url.intended',
                Subdirectory::normalizeRedirectUrl(
                    $request->session()->get('url.intended'),
                    Subdirectory::applicationUrl('/dashboard'),
                ),
            );
        }

        return $next($request);
    }
}
