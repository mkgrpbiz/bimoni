<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminManagerController extends Controller
{
    private function requireAdmin(): void
    {
        if (!auth('web')->user()?->isAdmin()) {
            abort(403, 'この操作は管理者のみ実行できます。');
        }
    }

    public static function menuKeys(): array
    {
        return [
            'dashboard'             => 'ダッシュボード',
            'campaigns'             => '案件管理',
            'daily_slots'           => '日別件数管理',
            'approval_reflections'  => '承認反映',
            'campaign_bonuses'      => 'キャンペーン',
            'applications'          => '応募管理',
            'proposal_reservations' => '打診予約',
            'reports'               => '報告管理',
            'collection_reports'    => '回収管理',
            'users'                 => 'ユーザー管理',
            'line_links'            => 'LINE紐付け',
            'points'                => '協力金管理',
            'referrals'             => '紹介報酬',
            'agents'                => '代理店',
            'import'                => 'インポート',
            'form_fields'           => 'ページ編集',
        ];
    }

    public function index(): View
    {
        $this->requireAdmin();
        $admins = Admin::orderBy('id')->get();
        return view('admin.admins.index', compact('admins'));
    }

    public function create(): View
    {
        $this->requireAdmin();
        $menuKeys = self::menuKeys();
        return view('admin.admins.create', compact('menuKeys'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireAdmin();
        $request->validate([
            'name'             => 'required|string|max:100',
            'email'            => 'required|email|unique:admins,email',
            'role'             => 'required|in:admin,operator',
            'accessible_menus' => 'nullable|array',
        ]);

        Admin::create([
            'name'             => $request->name,
            'email'            => $request->email,
            'password'         => bcrypt('bimoni1234'),
            'role'             => $request->role,
            'accessible_menus' => $request->role === 'operator' ? ($request->accessible_menus ?? []) : null,
        ]);

        return redirect()->route('admin.admins.index')->with('success', '管理者を追加しました。初期パスワード: bimoni1234');
    }

    public function edit(Admin $admin): View
    {
        $this->requireAdmin();
        $menuKeys = self::menuKeys();
        return view('admin.admins.edit', compact('admin', 'menuKeys'));
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $this->requireAdmin();
        $request->validate([
            'name'             => 'required|string|max:100',
            'email'            => 'required|email|unique:admins,email,' . $admin->id,
            'role'             => 'required|in:admin,operator',
            'accessible_menus' => 'nullable|array',
        ]);

        $admin->update([
            'name'             => $request->name,
            'email'            => $request->email,
            'role'             => $request->role,
            'accessible_menus' => $request->role === 'operator' ? ($request->accessible_menus ?? []) : null,
        ]);

        return redirect()->route('admin.admins.index')->with('success', '更新しました。');
    }

    public function destroy(Admin $admin): RedirectResponse
    {
        $this->requireAdmin();

        if ($admin->id === auth('web')->id()) {
            return back()->with('error', '自分自身は削除できません。');
        }

        $admin->delete();
        return redirect()->route('admin.admins.index')->with('success', '削除しました。');
    }

    public function resetPassword(Admin $admin): RedirectResponse
    {
        $this->requireAdmin();
        $admin->update(['password' => bcrypt('bimoni1234')]);
        return back()->with('success', 'パスワードを bimoni1234 にリセットしました。');
    }
}
