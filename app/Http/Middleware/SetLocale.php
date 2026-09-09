<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next, string $locale = 'ar'): Response
    {
        $locale = $locale === 'en' ? 'en' : 'ar';
        app()->setLocale($locale);

        return $next($request);
    }
}
