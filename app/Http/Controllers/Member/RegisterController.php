<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = Auth::guard('liff')->user();
        if ($user->profile_completed_at) {
            return redirect()->route('member.campaigns.index');
        }

        $terms   = LegalPage::terms();
        $privacy = LegalPage::privacy();

        return view('member.register', compact('user', 'terms', 'privacy'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::guard('liff')->user();

        $request->validate([
            'name'                => 'required|string|max:50',
            'name_kana'           => 'required|string|max:100',
            'gender'              => 'required|in:male,female',
            'birthdate'           => 'required|date',
            'email'               => 'nullable|email|max:255',
            'referred_by_code'    => 'nullable|string|max:10',
            'bank_account_number' => 'nullable|digits_between:7,8',
            'agree_terms'         => 'accepted',
        ], ['agree_terms.accepted' => '利用規約とプライバシーポリシーへの同意が必要です。']);

        $user->update([
            'name'                => $request->name,
            'name_kana'           => $request->name_kana,
            'gender'              => $request->gender,
            'birthdate'           => $request->birthdate,
            'email'               => $request->email,
            'referred_by_code'    => $request->referred_by_code ?: null,
            'profile_completed_at' => now(),
        ]);

        $this->saveBank($user, $request);

        return redirect()->route('member.campaigns.index');
    }

    public function edit(): View
    {
        $user = Auth::guard('liff')->user();
        return view('member.profile.edit', compact('user'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::guard('liff')->user();

        $request->validate([
            'name'                => 'required|string|max:50',
            'name_kana'           => 'required|string|max:100',
            'gender'              => 'required|in:male,female',
            'birthdate'           => 'required|date',
            'email'               => 'nullable|email|max:255',
            'bank_account_number' => 'nullable|digits_between:7,8',
        ]);

        $user->update([
            'name'      => $request->name,
            'name_kana' => $request->name_kana,
            'gender'    => $request->gender,
            'birthdate' => $request->birthdate,
            'email'     => $request->email,
        ]);

        $this->saveBank($user, $request);

        return back()->with('success', 'プロフィールを更新しました。');
    }

    private function saveBank($user, Request $request): void
    {
        $user->update([
            'bank_name'           => $request->input('bank_name'),
            'bank_code'           => $request->input('bank_code'),
            'bank_branch_name'    => $request->input('bank_branch_name'),
            'bank_branch_code'    => $request->input('bank_branch_code'),
            'bank_account_type'   => $request->input('bank_account_type'),
            'bank_account_number' => $request->input('bank_account_number'),
            'bank_account_name'   => $request->input('bank_account_name'),
        ]);
    }
}
