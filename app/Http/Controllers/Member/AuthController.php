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
    public function login(): View|RedirectResponse
    {
        if (Auth::guard('liff')->check()) {
            return $this->redirectAfterLogin(Auth::guard('liff')->user());
        }

        $devMode = empty(config('services.line.liff_id'));
        $testUsers = $devMode ? User::whereNotNull('name')->orderBy('name')->get() : collect();

        return view('member.auth.login', compact('devMode', 'testUsers'));
    }

    // 本番LIFF: JavaScript から POST される
    public function liffCallback(Request $request): JsonResponse
    {
        $request->validate(['line_user_id' => 'required|string']);

        $user = $this->findOrCreateUser($request->line_user_id);

        if ($request->filled('line_display_name')) {
            $user->update(['line_display_name' => $request->line_display_name]);
        }

        Auth::guard('liff')->login($user);

        $redirect = $user->profile_completed_at
            ? route('member.campaigns.index')
            : route('member.register');

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
