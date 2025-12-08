<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'v1',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'company.auth' => \App\Http\Middleware\CompanyAuth::class,
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, $request) {
            // Always return JSON for all requests (API-only application)

            // Validation errors
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'status' => 422,
                    'message' => 'Validation failed',
                    'data' => [
                        'errors' => $e->errors()
                    ]
                ], 422);
            }

            // Method Not Allowed
            if ($e instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'success' => false,
                    'status' => 405,
                    'message' => 'Método no permitido.',
                    'data' => (object)[]
                ], 405);
            }

            // Not Found
            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'status' => 404,
                    'message' => 'Endpoint no encontrado.',
                    'data' => (object)[]
                ], 404);
            }

            // Authentication
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'status' => 401,
                    'message' => 'No autenticado.',
                    'data' => (object)[]
                ], 401);
            }

            // HTTP Exceptions
            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'success' => false,
                    'status' => $e->getStatusCode(),
                    'message' => $e->getMessage() ?: 'Ha ocurrido un error.',
                    'data' => (object)[]
                ], $e->getStatusCode());
            }

            // General errors
            $statusCode = 500;

            return response()->json([
                'success' => false,
                'status' => $statusCode,
                'message' => $e->getMessage() ?: 'Error interno del servidor.',
                'data' => config('app.debug') ? [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ] : (object)[]
            ], $statusCode);
        });
    })->create();