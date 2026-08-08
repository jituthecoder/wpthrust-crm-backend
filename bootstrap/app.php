<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;



return Application::configure(basePath: dirname(__DIR__))

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    */

    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */

    ->withMiddleware(function (Middleware $middleware) {

        /*
        |--------------------------------------------------------------------------
        | Role Middleware
        |--------------------------------------------------------------------------
        */

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Authentication Redirect
        |--------------------------------------------------------------------------
        |
        | API requests should never be redirected to a login page.
        | They should return a JSON 401 response instead.
        |
        */

        $middleware->redirectTo(function (Request $request) {

            if ($request->is('api/*')) {
                return null;
            }

            return '/login';

        });

    })

    /*
    |--------------------------------------------------------------------------
    | Exception Handling
    |--------------------------------------------------------------------------
    */

    ->withExceptions(function (Exceptions $exceptions): void {

        /*
        |--------------------------------------------------------------------------
        | Validation Errors - 422
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            ValidationException $e,
            Request $request
        ) {

            if (
                !$request->is('api/*') &&
                !$request->expectsJson()
            ) {
                return null;
            }

            return response()->json([

                'success' => false,

                'message' => 'The given data was invalid.',

                'errors' => $e->errors(),

            ], 422);
        });


        /*
        |--------------------------------------------------------------------------
        | Authentication Errors - 401
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) {

            if (
                !$request->is('api/*') &&
                !$request->expectsJson()
            ) {
                return null;
            }

            return response()->json([

                'success' => false,

                'message' => 'Unauthenticated.',

                'errors' => null,

            ], 401);
        });


        /*
        |--------------------------------------------------------------------------
        | Authorization Errors - 403
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            AuthorizationException $e,
            Request $request
        ) {

            if (
                !$request->is('api/*') &&
                !$request->expectsJson()
            ) {
                return null;
            }

            return response()->json([

                'success' => false,

                'message' => 'You do not have permission to perform this action.',

                'errors' => null,

            ], 403);
        });


        /*
        |--------------------------------------------------------------------------
        | Model Not Found - 404
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            ModelNotFoundException $e,
            Request $request
        ) {

            if (
                !$request->is('api/*') &&
                !$request->expectsJson()
            ) {
                return null;
            }

            return response()->json([

                'success' => false,

                'message' => 'The requested resource was not found.',

                'errors' => null,

            ], 404);
        });


        /*
        |--------------------------------------------------------------------------
        | Route Not Found - 404
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            NotFoundHttpException $e,
            Request $request
        ) {

            if (
                !$request->is('api/*') &&
                !$request->expectsJson()
            ) {
                return null;
            }

            return response()->json([

                'success' => false,

                'message' => 'The requested resource was not found.',

                'errors' => null,

            ], 404);
        });


        /*
        |--------------------------------------------------------------------------
        | Method Not Allowed - 405
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            MethodNotAllowedHttpException $e,
            Request $request
        ) {

            if (
                !$request->is('api/*') &&
                !$request->expectsJson()
            ) {
                return null;
            }

            return response()->json([

                'success' => false,

                'message' => 'The requested method is not allowed.',

                'errors' => null,

            ], 405);
        });


        /*
        |--------------------------------------------------------------------------
        | Too Many Requests - 429
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            TooManyRequestsHttpException $e,
            Request $request
        ) {

            if (
                !$request->is('api/*') &&
                !$request->expectsJson()
            ) {
                return null;
            }

            return response()->json([

                'success' => false,

                'message' => 'Too many requests. Please try again later.',

                'errors' => null,

            ], 429);
        });


        /*
        |--------------------------------------------------------------------------
        | Other HTTP Exceptions
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            HttpExceptionInterface $e,
            Request $request
        ) {

            if (
                !$request->is('api/*') &&
                !$request->expectsJson()
            ) {
                return null;
            }

            return response()->json([

                'success' => false,

                'message' => $e->getMessage()
                    ?: 'An error occurred.',

                'errors' => null,

            ], $e->getStatusCode());
        });


        /*
        |--------------------------------------------------------------------------
        | Generic API Errors - 500
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            \Throwable $e,
            Request $request
        ) {

            if (
                !$request->is('api/*') &&
                !$request->expectsJson()
            ) {
                return null;
            }

            return response()->json([

                'success' => false,

                'message' => app()->environment('production')
                    ? 'Something went wrong. Please try again later.'
                    : $e->getMessage(),

                'errors' => null,

            ], 500);
        });

    })

    ->create();