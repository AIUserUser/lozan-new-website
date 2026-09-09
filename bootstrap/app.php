<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('admin*') || $request->expectsJson()) {
                return null;
            }
            $path = $request->path();
            if (str_starts_with($path, 'en/') || $path === 'en') {
                app()->setLocale('en');
            } else {
                app()->setLocale('ar');
            }

            return response()->view('store.not-found', [
                'seo' => app(\App\Services\SeoService::class)->page('not-found', $path),
            ], 404);
        });
    })->create();
