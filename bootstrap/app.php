<?php

use App\Http\Middleware\ApiLogger;
use App\Http\Middleware\CheckPermission;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->appendToGroup('api', ApiLogger::class);
        $middleware->alias(['permission' => CheckPermission::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                Log::channel('api')->warning('API Unauthenticated', [
                    'url'    => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip'     => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please login first.',
                ], 401);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                Log::channel('api')->warning('API Validation Error', [
                    'url'    => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip'     => $request->ip(),
                    'errors' => $e->errors(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                Log::channel('api')->error('API Exception', [
                    'url'     => $request->fullUrl(),
                    'method'  => $request->method(),
                    'ip'      => $request->ip(),
                    'user_id' => optional($request->user())->id,
                    'error'   => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'trace'   => collect($e->getTrace())->take(5)->toArray(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Server error. Please try again later.',
                    'error'   => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }
        });
    })->create();
