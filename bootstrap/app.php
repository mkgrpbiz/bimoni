<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['portal.auth' => \App\Http\Middleware\PortalAuth::class]);
        $middleware->validateCsrfTokens(except: ['member/auth/liff-callback', 'member/register']);
        // member/* へのアクセスは member.login へリダイレクト
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('member/*') || $request->is('member')) {
                return route('member.login');
            }
            return route('admin.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
