<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Always answer API/JSON clients with JSON, even without an Accept header.
        $wantsJson = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $wantsJson($request));

        // Normalize every API error into a single { message, errors? } envelope
        // with the status codes required by the spec.
        $exceptions->render(function (Throwable $e, Request $request) use ($wantsJson) {
            if (! $wantsJson($request)) {
                return null; // fall back to default (web) rendering
            }

            [$status, $payload] = match (true) {
                $e instanceof ValidationException => [
                    422,
                    ['message' => $e->getMessage(), 'errors' => $e->errors()],
                ],
                $e instanceof AuthenticationException => [
                    401,
                    ['message' => 'Unauthenticated.'],
                ],
                $e instanceof AuthorizationException,
                $e instanceof AccessDeniedHttpException => [
                    403,
                    ['message' => $e->getMessage() ?: 'This action is unauthorized.'],
                ],
                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => [
                    404,
                    ['message' => 'Resource not found.'],
                ],
                $e instanceof HttpExceptionInterface => [
                    $e->getStatusCode(),
                    ['message' => $e->getMessage() ?: 'Request failed.'],
                ],
                default => [
                    500,
                    [
                        'message' => config('app.debug')
                            ? $e->getMessage()
                            : 'Server error.',
                    ],
                ],
            };

            // Surface stack traces only while debugging, never in production.
            if ($status === 500 && config('app.debug')) {
                $payload['exception'] = $e::class;
                $payload['trace'] = collect($e->getTrace())->take(5)->all();
            }

            return response()->json($payload, $status);
        });
    })->create();
