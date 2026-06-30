<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
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
        return view('member.transfer', ['candidates' => collect()]);
    }

    public function search(Request $request): View|RedirectResponse
    {
        $user = Auth::guard('liff')->user();
        if ($user->profile_completed_at) {
            return redirect()->route('member.campaigns.index');
        }

        $request->validate([
            'name'      => 'required|string|max:50',
            'name_kana' => 'required|string|max:100',
        ], [
            'name.required'      => 'お名前を入力してください。',
            'name_kana.required' => 'フリガナを入力してください。',
        ]);

        $base = User::where(fn($q) => $q->whereNull('line_user_id')->orWhere('line_user_id', 'like', 'IMPORT_%'))
            ->where('imported_from', 'spreadsheet')
            ->where('name', $request->name)
            ->where('name_kana', $request->name_kana);

        // 生年月日 or メールで絞り込み
        $candidates = collect();
        if ($request->filled('birthdate')) {
            $candidates = (clone $base)->where('birthdate', $request->birthdate)->get();
        }
        if ($candidates->isEmpty() && $request->filled('email')) {
            $candidates = (clone $base)->where('email', $request->email)->get();
        }
        if ($candidates->isEmpty()) {
            $candidates = $base->get();
        }

        if ($candidates->count() === 1) {
            return $this->doLink($user, $candidates->first());
        }

        if ($candidates->isEmpty()) {
            return back()->withErrors(['name' => '一致するデータが見つかりませんでした。入力内容をご確認ください。'])->withInput();
        }

        // 複数候補 → 選択画面へ
        return view('member.transfer', compact('candidates'))->with('input', $request->only('name', 'name_kana', 'birthdate', 'email'));
    }

    public function link(Request $request, int $userId): RedirectResponse
    {
        $liffUser = Auth::guard('liff')->user();
        if ($liffUser->profile_completed_at) {
            return redirect()->route('member.campaigns.index');
        }

        $existing = User::where('id', $userId)
            ->where(fn($q) => $q->whereNull('line_user_id')->orWhere('line_user_id', 'like', 'IMPORT_%'))
            ->where('imported_from', 'spreadsheet')
            ->firstOrFail();

        return $this->doLink($liffUser, $existing);
    }

    private function doLink(User $liffUser, User $existing): RedirectResponse
    {
        $existing->update([
            'line_user_id'        => $liffUser->line_user_id,
            'line_display_name'   => $liffUser->line_display_name ?? $existing->line_display_name,
            'profile_completed_at' => $existing->profile_completed_at ?? now(),
        ]);

        $liffUser->delete();

        Auth::guard('liff')->login($existing);

        return redirect()->route('member.campaigns.index');
    }
}
