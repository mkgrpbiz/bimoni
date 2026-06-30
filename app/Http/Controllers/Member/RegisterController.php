<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use App\Models\User;
use App\Services\UserMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = Auth::guard('liff')->user();
        if ($user->profile_completed_at) {
            return redirect()->route('member.campaigns.index');
        }

        $terms   = LegalPage::terms();
        $privacy = LegalPage::privacy();

        // 既存紹介コード → セッション紹介コード → null の優先順
        $referralCode = $user->referred_by_code
            ?? $request->session()->get('referral_code');

        return view('member.register', compact('user', 'terms', 'privacy', 'referralCode'));
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

        // 既存インポートユーザーとの自動紐付け
        $linked = $this->tryLinkExistingUser($user, $request);
        if ($linked) {
            // 紐付け成功: 既存ユーザーとして再ログイン
            Auth::guard('liff')->login($linked);
            return redirect()->route('member.campaigns.index');
        }

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

    private function tryLinkExistingUser(User $liffUser, Request $request): ?User
    {
        $target = [
            'name'      => $request->name,
            'name_kana' => $request->name_kana,
            'birthdate' => $request->birthdate,
            'email'     => $request->email ? strtolower($request->email) : null,
        ];

        // インポート済みでまだLINE未紐付けのユーザーから候補を絞り込み
        $candidates = User::where(fn($q) => $q->whereNull('line_user_id')->orWhere('line_user_id', 'like', 'IMPORT_%'))
            ->where('imported_from', 'spreadsheet')
            ->where(function ($q) use ($target) {
                $q->where('name', $target['name'])
                  ->orWhere('name_kana', $target['name_kana']);
                if ($target['birthdate']) {
                    $q->orWhere('birthdate', $target['birthdate']);
                }
                if ($target['email']) {
                    $q->orWhere('email', $target['email']);
                }
            })
            ->get();

        // 名前・フリガナ・生年月日・メールのうち3項目以上一致がただ1件の場合のみ自動紐付け
        $existing = UserMatcher::findUniqueTopMatch($candidates, $target);
        if (!$existing) {
            return null;
        }

        // 既存ユーザーに LINE情報・プロフィール・銀行情報をマージ
        $existing->update([
            'line_user_id'        => $liffUser->line_user_id,
            'line_display_name'   => $liffUser->line_display_name,
            'name'                => $request->name,
            'name_kana'           => $request->name_kana,
            'gender'              => $request->gender,
            'birthdate'           => $request->birthdate,
            'email'               => $request->email,
            'referred_by_code'    => $existing->referred_by_code ?: ($request->referred_by_code ?: null),
            'profile_completed_at' => now(),
        ]);
        $this->saveBank($existing, $request);

        // LINEログイン時に作られた空ユーザーを削除
        $liffUser->delete();

        return $existing;
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
