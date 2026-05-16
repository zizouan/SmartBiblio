<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.jwt' => \App\Http\Middleware\JwtAuthMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\ApiException $e) {
            return \App\Support\ApiResponse::error($e->apiCode, $e->getMessage(), $e->status, $e->details);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return \App\Support\ApiResponse::error(
                'VALIDATION_ERROR',
                'Request validation failed.',
                422,
                $e->errors(),
            );
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return \App\Support\ApiResponse::error('NOT_FOUND', 'Resource not found.', 404);
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return \App\Support\ApiResponse::error('AUTH_REQUIRED', 'Authentication is required.', 401);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e) {
            return \App\Support\ApiResponse::error('FORBIDDEN', 'You are not allowed to perform this action.', 403);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            if ($e->getStatusCode() === 404) {
                return \App\Support\ApiResponse::error('NOT_FOUND', 'Resource not found.', 404);
            }

            return null;
        });
    })->create();
