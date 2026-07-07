<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LineLinkController extends Controller
{
    // インポートユーザー一覧（紐付け状況別）
    public function index(Request $request): View
    {
        $status = $request->get('status', 'unlinked');
        if (!in_array($status, ['unlinked', 'linked', 'transfer', 'new_register'], true)) {
            $status = 'unlinked';
        }

        if ($status === 'transfer') {
            $entries = $this->buildTransferEntries($request);
            return view('admin.line_links.index', ['status' => $status, 'entries' => $entries]);
        }

        if ($status === 'new_register') {
            $entries = $this->buildNewRegisterEntries($request);
            return view('admin.line_links.index', ['status' => $status, 'entries' => $entries]);
        }

        $query = User::where('imported_from', 'spreadsheet');

        if ($status === 'linked') {
            $query->whereNotNull('line_user_id')->where('line_user_id', 'not like', 'IMPORT_%');
        } else {
            $query->where(fn($q) => $q->whereNull('line_user_id')->orWhere('line_user_id', 'like', 'IMPORT_%'));
        }

        $query->orderByDesc('created_at');

        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->name . '%')
                  ->orWhere('name_kana', 'like', '%' . $request->name . '%')
                  ->orWhere('erme_respondent_id', 'like', '%' . $request->name . '%');
            });
        }

        $unlinked = $query->paginate(50)->withQueryString();

        return view('admin.line_links.index', compact('unlinked', 'status'));
    }

    // 通常登録（紐付け未確定）一覧 + それぞれの候補一覧を作成
    private function buildNewRegisterEntries(Request $request): LengthAwarePaginator
    {
        $importPool = User::where('imported_from', 'spreadsheet')
            ->where(fn($q) => $q->whereNull('line_user_id')->orWhere('line_user_id', 'like', 'IMPORT_%'))
            ->get();

        $query = User::where('imported_from', 'new')
            ->whereNotNull('profile_completed_at')
            ->whereNull('transfer_registered_at')
            ->whereNull('new_register_confirmed_at')
            ->whereNotNull('line_user_id')
            ->where('line_user_id', 'not like', 'IMPORT_%')
            ->orderByDesc('profile_completed_at');

        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->name . '%')
                  ->orWhere('name_kana', 'like', '%' . $request->name . '%');
            });
        }

        $newUsers = $query->get();

        $entries = $newUsers->map(function (User $user) use ($importPool) {
            $target = UserMatcher::fields($user);
            return [
                'user'       => $user,
                'candidates' => UserMatcher::scoredCandidates($importPool, $target, 1),
            ];
        });

        $perPage = 50;
        $page = max((int) $request->get('page', 1), 1);

        return new LengthAwarePaginator(
            $entries->forPage($page, $perPage)->values(),
            $entries->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    // 引き継ぎ登録（紐付け未確定）一覧 + それぞれの候補一覧を作成
    private function buildTransferEntries(Request $request): LengthAwarePaginator
    {
        $importPool = User::where('imported_from', 'spreadsheet')
            ->where(fn($q) => $q->whereNull('line_user_id')->orWhere('line_user_id', 'like', 'IMPORT_%'))
            ->get();

        $query = User::whereNotNull('transfer_registered_at')->orderByDesc('transfer_registered_at');

        if ($request->filled('name')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->name . '%')
                  ->orWhere('name_kana', 'like', '%' . $request->name . '%');
            });
        }

        $transferUsers = $query->get();

        $entries = $transferUsers->map(function (User $user) use ($importPool) {
            $target = UserMatcher::fields($user);
            return [
                'user'       => $user,
                'candidates' => UserMatcher::scoredCandidates($importPool, $target, 1),
            ];
        });

        $perPage = 50;
        $page = max((int) $request->get('page', 1), 1);

        return new LengthAwarePaginator(
            $entries->forPage($page, $perPage)->values(),
            $entries->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    // 手動紐付けモーダル用: LINE登録済みユーザー検索（JSON）
    public function searchLiff(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|min:1']);

        $liffUsers = User::whereNotNull('line_user_id')
            ->whereNotNull('profile_completed_at')
            ->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->name . '%')
                  ->orWhere('name_kana', 'like', '%' . $request->name . '%')
                  ->orWhere('line_display_name', 'like', '%' . $request->name . '%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'name_kana', 'line_display_name', 'birthdate', 'gender'])
            ->map(fn ($u) => [
                'id'               => $u->id,
                'name'             => $u->name,
                'name_kana'        => $u->name_kana,
                'line_display_name' => $u->line_display_name,
                'birthdate'        => $u->birthdate?->format('Y-m-d'),
                'gender'           => $u->gender,
            ]);

        return response()->json($liffUsers);
    }

    // 手動紐付けモーダル用: 未紐付きインポートユーザー検索（JSON）
    public function searchImport(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|min:1']);

        $importUsers = User::where('imported_from', 'spreadsheet')
            ->where(fn($q) => $q->whereNull('line_user_id')->orWhere('line_user_id', 'like', 'IMPORT_%'))
            ->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->name . '%')
                  ->orWhere('name_kana', 'like', '%' . $request->name . '%')
                  ->orWhere('erme_respondent_id', 'like', '%' . $request->name . '%')
                  ->orWhere('line_display_name', 'like', '%' . $request->name . '%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'name_kana', 'line_display_name', 'birthdate', 'gender', 'erme_respondent_id'])
            ->map(fn ($u) => [
                'id'                 => $u->id,
                'name'               => $u->name,
                'name_kana'          => $u->name_kana,
                'line_display_name'  => $u->line_display_name,
                'birthdate'          => $u->birthdate?->format('Y-m-d'),
                'gender'             => $u->gender,
                'erme_respondent_id' => $u->erme_respondent_id,
            ]);

        return response()->json($importUsers);
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

        // LIFFユーザーの応募・報告データをインポートユーザーに移動
        // line_user_id にユニーク制約があるため、liffUser を先に削除してから importUser を更新する
        DB::transaction(function () use ($importUser, $liffUser) {
            $lineUserId        = $liffUser->line_user_id;
            $lineDisplayName   = $liffUser->line_display_name;
            $profileCompletedAt = $liffUser->profile_completed_at ?? now();

            $liffUser->applications()->update(['user_id' => $importUser->id]);
            $liffUser->monitorReports()->update(['user_id' => $importUser->id]);
            $liffUser->points()->update(['user_id' => $importUser->id]);

            $liffUser->delete();

            $importUser->update([
                'line_user_id'        => $lineUserId,
                'line_display_name'   => $lineDisplayName,
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
                'profile_completed_at' => $profileCompletedAt,
            ]);
        });

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

    // 引き継ぎ登録ユーザーを正真正銘の新規として確定（紐付け候補一覧から外す）
    public function confirmNew(Request $request): RedirectResponse
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        User::findOrFail($request->user_id)
            ->update(['transfer_registered_at' => null]);

        return back()->with('success', '新規ユーザーとして確定しました。');
    }

    // 通常登録ユーザーを正真正銘の新規として確定（新規登録タブから外す）
    public function confirmNewRegister(Request $request): RedirectResponse
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        User::findOrFail($request->user_id)
            ->update(['new_register_confirmed_at' => now()]);

        return back()->with('success', '新規ユーザーとして確定しました。');
    }
}
