<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(Request $request): View|RedirectResponse
    {
        // liff.state 経由のパラメータを server 側でデコード
        // （liff.init()の内部リダイレクトで元のクエリパラメータが liff.state に
        // 格納されてしまい、window.location.search から読めなくなることがあるため）
        $from = $request->get('from', '');
        $referralCode = $request->get('referral_code', '');
        if (!$from || !$referralCode) {
            // PHPはクエリパラメータ名のドットをアンダースコアに変換するため liff_state で取得
            $liffState = $request->get('liff_state', '');
            parse_str(ltrim($liffState, '?'), $stateParams);
            $from = $from ?: ($stateParams['from'] ?? '');
            $referralCode = $referralCode ?: ($stateParams['referral_code'] ?? '');
        }

        if (Auth::guard('liff')->check()) {
            $user = Auth::guard('liff')->user();
            if (!$user->profile_completed_at && $from === 'transfer') {
                return redirect()->route('member.transfer');
            }
            return $this->redirectAfterLogin($user);
        }

        $devMode = empty(config('services.line.liff_id'));
        $testUsers = $devMode ? User::whereNotNull('name')->orderBy('name')->get() : collect();

        return view('member.auth.login', compact('devMode', 'testUsers', 'from', 'referralCode'));
    }

    // 本番LIFF: JavaScript から POST される
    public function liffCallback(Request $request): JsonResponse
    {
        $request->validate(['line_user_id' => 'required|string']);

        $user = $this->findOrCreateUser($request->line_user_id);

        if ($request->filled('line_display_name')) {
            $user->update(['line_display_name' => $request->line_display_name]);
        }

        // 招待リンク経由の紹介コードをDBに直接保存
        // 代理店の招待コード（AgentReferralCode）とユーザー個人の紹介コード（bimoni_user_id）を判定して振り分ける
        if ($request->filled('referral_code') && !$user->referred_by_code && !$user->referred_by_user_id) {
            $code = strtoupper($request->referral_code);
            if (\App\Models\AgentReferralCode::where('code', $code)->exists()) {
                $user->update(['referred_by_code' => $code]);
            } else {
                $referrer = User::where('bimoni_user_id', $code)->first();
                if ($referrer && $referrer->id !== $user->id) {
                    $user->update(['referred_by_user_id' => $referrer->id]);
                }
            }
        }

        Auth::guard('liff')->login($user);

        if ($user->profile_completed_at) {
            $redirect = route('member.campaigns.index');
        } elseif ($request->input('redirect_to') === 'transfer') {
            $redirect = route('member.transfer');
        } else {
            $redirect = route('member.register');
        }

        return response()->json(['redirect' => $redirect]);
    }

    // 開発用: フォームから POST される
    public function devLogin(Request $request): RedirectResponse
    {
        abort_if(!empty(config('services.line.liff_id')), 403);

        $request->validate([
            'user_id'       => 'nullable|exists:users,id',
            'test_line_uid' => 'nullable|string',
        ]);

        if ($request->user_id) {
            $user = User::findOrFail($request->user_id);
        } else {
            $uid  = $request->test_line_uid ?: 'DEV_' . uniqid();
            $user = $this->findOrCreateUser($uid);
        }

        Auth::guard('liff')->login($user);

        return $this->redirectAfterLogin($user);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('liff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('member.login');
    }

    private function findOrCreateUser(string $lineUserId): User
    {
        return User::firstOrCreate(
            ['line_user_id' => $lineUserId],
            ['imported_from' => 'new', 'status' => 'active']
        );
    }

    private function redirectAfterLogin(User $user): RedirectResponse
    {
        return $user->profile_completed_at
            ? redirect()->route('member.campaigns.index')
            : redirect()->route('member.register');
    }
}
