<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LineLinkController extends Controller
{
    // 未紐付きインポートユーザー一覧
    public function index(Request $request): View
    {
        $query = User::where(fn($q) => $q->whereNull('line_user_id')->orWhere('line_user_id', 'like', 'IMPORT_%'))
            ->where('imported_from', 'spreadsheet')
            ->orderBy('name');

        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->name . '%')
                  ->orWhere('name_kana', 'like', '%' . $request->name . '%');
            });
        }

        $unlinked = $query->paginate(50)->withQueryString();

        return view('admin.line_links.index', compact('unlinked'));
    }

    // 手動紐付けモーダル用: LINE登録済みユーザー検索（JSON）
    public function searchLiff(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|min:1']);

        $liffUsers = User::whereNotNull('line_user_id')
            ->whereNotNull('profile_completed_at')
            ->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->name . '%')
                  ->orWhere('name_kana', 'like', '%' . $request->name . '%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'name_kana', 'birthdate', 'gender']);

        return response()->json($liffUsers);
    }

    // 手動紐付け実行
    public function link(Request $request): RedirectResponse
    {
        $request->validate([
            'import_user_id' => 'required|exists:users,id',
            'liff_user_id'   => 'required|exists:users,id',
        ]);

        $importUser = User::findOrFail($request->import_user_id);
        $liffUser   = User::findOrFail($request->liff_user_id);

        // 既に紐付け済みのチェック（IMPORT_xxx は未紐付けとみなす）
        if ($importUser->line_user_id && !str_starts_with($importUser->line_user_id, 'IMPORT_')) {
            return back()->withErrors(['error' => 'このユーザーはすでにLINEアカウントと紐付けられています。']);
        }

        // インポートユーザーにLIFF情報をマージ
        $importUser->update([
            'line_user_id'        => $liffUser->line_user_id,
            'line_display_name'   => $liffUser->line_display_name,
            'name'                => $liffUser->name ?: $importUser->name,
            'name_kana'           => $liffUser->name_kana ?: $importUser->name_kana,
            'gender'              => $liffUser->gender ?: $importUser->gender,
            'birthdate'           => $liffUser->birthdate ?: $importUser->birthdate,
            'email'               => $liffUser->email ?: $importUser->email,
            'bank_name'           => $liffUser->bank_name ?: $importUser->bank_name,
            'bank_code'           => $liffUser->bank_code ?: $importUser->bank_code,
            'bank_branch_name'    => $liffUser->bank_branch_name ?: $importUser->bank_branch_name,
            'bank_branch_code'    => $liffUser->bank_branch_code ?: $importUser->bank_branch_code,
            'bank_account_type'   => $liffUser->bank_account_type ?: $importUser->bank_account_type,
            'bank_account_number' => $liffUser->bank_account_number ?: $importUser->bank_account_number,
            'bank_account_name'   => $liffUser->bank_account_name ?: $importUser->bank_account_name,
            'profile_completed_at' => $liffUser->profile_completed_at ?? now(),
        ]);

        // LIFFユーザーの応募・報告データをインポートユーザーに移動
        $liffUser->applications()->update(['user_id' => $importUser->id]);
        $liffUser->monitorReports()->update(['user_id' => $importUser->id]);
        $liffUser->points()->update(['user_id' => $importUser->id]);

        // LIFFユーザー（空）を削除
        $liffUser->delete();

        return redirect()->route('admin.line_links.index')
            ->with('success', "{$importUser->name} のLINEアカウントを紐付けました。");
    }

    // インポートユーザーを新規として扱う（紐付けをスキップ）
    public function skip(Request $request): RedirectResponse
    {
        $request->validate(['import_user_id' => 'required|exists:users,id']);

        User::findOrFail($request->import_user_id)
            ->update(['imported_from' => 'spreadsheet_skipped']);

        return back()->with('success', 'スキップしました。');
    }
}
