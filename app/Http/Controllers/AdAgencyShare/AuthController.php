<?php

namespace App\Http\Controllers\AdAgencyShare;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('ad_agency_share.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::guard('ad_agency_share')->attempt($credentials)) {
            return back()->withErrors(['email' => 'メールアドレスまたはパスワードが違います。'])->onlyInput('email');
        }

        $request->session()->regenerate();

        Auth::guard('ad_agency_share')->user()->update(['last_login_at' => now()]);

        return redirect()->route('ad_agency_share.campaigns');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('ad_agency_share')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('ad_agency_share.login');
    }
}
