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
        $middleware->alias([
            'portal.auth' => \App\Http\Middleware\PortalAuth::class,
            'ai-office.token' => \App\Http\Middleware\VerifyAiOfficeToken::class,
        ]);
        $middleware->validateCsrfTokens(except: ['member/auth/liff-callback', 'member/register', 'member/transfer', 'api/ai-office/bimoni/campaigns/draft']);
        // member/* へのアクセスは member.login へリダイレクト
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('member/*') || $request->is('member')) {
                $loginUrl = route('member.login');
                if ($request->is('member/transfer')) {
                    $loginUrl .= '?from=transfer';
                }
                return $loginUrl;
            }
            if ($request->is('agency-share/*') || $request->is('agency-share')) {
                return route('ad_agency_share.login');
            }
            return route('admin.login');
        });
        // guest:xxx ミドルウェアは認証済みユーザーが「ログイン画面」等にアクセスした際にここへリダイレクトする。
        // アプリに 'dashboard'/'home' という名前のルートが無いため、未設定だとフレームワークの既定処理が
        // 常に '/' へ飛ばし、'/' は admin.login へリダイレクトするため、ログイン済みで /admin/login や
        // /agency-share/login を開くと無限リダイレクトループになるバグがあった（2026-08-31発見）
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->is('agency-share/*') || $request->is('agency-share')) {
                return route('ad_agency_share.campaigns');
            }
            return route('admin.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
