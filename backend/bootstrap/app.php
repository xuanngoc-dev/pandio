<?php

use App\Exceptions\ApiExceptionHandler;
use App\Http\Middleware\LogApiRequestResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Log mọi request/response trên nhóm route api.php
        $middleware->api(append: [
            LogApiRequestResponse::class,
        ]);

        // Chỉ áp dụng cho web (api-docs). API trả JSON 401, không redirect HTML.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            return route('api-docs.login');
        });
        $middleware->redirectUsersTo(fn () => route('api-docs'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => ApiExceptionHandler::wantsApi($request),
        );

        // Web (api-docs): guest chưa login → redirect form login
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (ApiExceptionHandler::wantsApi($request)) {
                return null; // để handler API xử lý JSON
            }

            return redirect()->guest(route('api-docs.login'));
        });

        // Mọi exception trên /api → JSON thống nhất cho FE
        $exceptions->render(function (\Throwable $e, Request $request) {
            return ApiExceptionHandler::render($e, $request);
        });
    })->create();
