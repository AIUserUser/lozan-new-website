<?php

namespace App\Http\Middleware;

use App\Services\Analytics\Tracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackStorefront
{
    public function __construct(private Tracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->tracker->shouldTrack($request)) {
            return $response;
        }

        $page = Tracker::pageFor($request->path());
        if ($request->isMethod('GET') && $response->getStatusCode() === 200 && $page !== null) {
            $this->tracker->pageView($request, $page, $request->attributes->get('analytics_product_id'));
        }

        $visitorId = $request->attributes->get(Tracker::COOKIE);
        if ($visitorId && $visitorId !== $request->cookie(Tracker::COOKIE)) {
            $response->headers->setCookie(cookie(Tracker::COOKIE, $visitorId, Tracker::COOKIE_MINUTES));
        }

        return $response;
    }
}
