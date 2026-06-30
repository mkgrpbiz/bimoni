<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LegalPage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = Auth::guard('liff')->user();
        if ($user->profile_completed_at) {
            return redirect()->route('member.campaigns.index');
        }

        $terms   = LegalPage::terms();
        $privacy = LegalPage::privacy();

        return view('member.transfer', compact('terms', 'privacy'));
    }

    public function store(Request $request): RedirectResponse
    {
        $liffUser = Auth::guard('liff')->user();
        if ($liffUser->profile_completed_at) {
            return redirect()->route('member.campaigns.index');
        }

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
            'agree_terms'         => 'accepted',
        ], [
            'agree_terms.accepted'       => '利用規約とプライバシーポリシーへの同意が必要です。',
            'bank_account_name.regex'    => '口座名義にスペースは使用できません。',
            'bank_account_number.digits_between' => '口座番号は7〜8桁で入力してください。',
        ]);

        $base = User::where(fn($q) => $q->whereNull('line_user_id')->orWhere('line_user_id', 'like', 'IMPORT_%'))
            ->where('imported_from', 'spreadsheet')
            ->where('name', $request->name)
            ->where('name_kana', $request->name_kana);

        // 名前+フリガナ+生年月日
        $matched = (clone $base)->where('birthdate', $request->birthdate)->get();

        // 名前+フリガナ+メール
        if ($matched->count() !== 1) {
            $matched = (clone $base)->where('email', $request->email)->get();
        }

        // 名前+フリガナのみ
        if ($matched->count() !== 1) {
            $matched = $base->get();
        }

        if ($matched->count() === 0) {
            return back()->withErrors(['name' => '一致するデータが見つかりませんでした。入力内容をご確認ください。'])->withInput();
        }

        if ($matched->count() > 1) {
            return back()->withErrors(['name' => '複数のデータが見つかりました。運営にお問い合わせください。'])->withInput();
        }

        $existing = $matched->first();

        $existing->update([
            'line_user_id'         => $liffUser->line_user_id,
            'line_display_name'    => $liffUser->line_display_name ?? $existing->line_display_name,
            'name'                 => $request->name,
            'name_kana'            => $request->name_kana,
            'gender'               => $request->gender,
            'birthdate'            => $request->birthdate,
            'email'                => $request->email,
            'profile_completed_at' => $existing->profile_completed_at ?? now(),
            'bank_name'            => $request->bank_name,
            'bank_code'            => $request->bank_code ?: null,
            'bank_branch_name'     => $request->bank_branch_name,
            'bank_branch_code'     => $request->bank_branch_code ?: null,
            'bank_account_type'    => $request->bank_account_type,
            'bank_account_number'  => $request->bank_account_number,
            'bank_account_name'    => preg_replace('/\s+/', '', $request->bank_account_name),
        ]);

        $liffUser->delete();

        Auth::guard('liff')->login($existing);

        return redirect()->route('member.campaigns.index');
    }
}
