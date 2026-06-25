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
            'email'               => 'required|email|max:255',
            'referred_by_code'    => 'nullable|string|max:10',
            'bank_name'           => 'required|string|max:100',
            'bank_branch_name'    => 'required|string|max:100',
            'bank_account_type'   => 'required|in:普通,当座',
            'bank_account_number' => 'required|digits_between:7,8',
            'bank_account_name'   => 'required|string|max:100|regex:/^\S+$/',
            'agree_terms'         => 'accepted',
        ], [
            'agree_terms.accepted'       => '利用規約とプライバシーポリシーへの同意が必要です。',
            'bank_account_name.regex'    => '口座名義にスペースは使用できません。',
            'bank_account_number.digits_between' => '口座番号は7〜8桁で入力してください。',
        ]);

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
            'email'               => 'required|email|max:255',
            'bank_name'           => 'required|string|max:100',
            'bank_branch_name'    => 'required|string|max:100',
            'bank_account_type'   => 'required|in:普通,当座',
            'bank_account_number' => 'required|digits_between:7,8',
            'bank_account_name'   => 'required|string|max:100|regex:/^\S+$/',
        ], [
            'bank_account_name.regex'    => '口座名義にスペースは使用できません。',
            'bank_account_number.digits_between' => '口座番号は7〜8桁で入力してください。',
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
            'bank_code'           => $request->input('bank_code') ?: null,
            'bank_branch_name'    => $request->input('bank_branch_name'),
            'bank_branch_code'    => $request->input('bank_branch_code') ?: null,
            'bank_account_type'   => $request->input('bank_account_type'),
            'bank_account_number' => $request->input('bank_account_number'),
            'bank_account_name'   => preg_replace('/\s+/', '', $request->input('bank_account_name', '')),
        ]);
    }
}
