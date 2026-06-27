<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function edit(): View
    {
        $admin = auth('web')->user();
        return view('admin.profile.edit', compact('admin'));
    }

    public function update(Request $request): RedirectResponse
    {
        $admin = auth('web')->user();

        $request->validate([
            'email'            => 'required|email|unique:admins,email,' . $admin->id,
            'current_password' => 'nullable|string',
            'password'         => 'nullable|string|min:8|confirmed',
        ]);

        // パスワード変更する場合は現在のパスワードを確認
        if ($request->filled('password')) {
            if (!$request->filled('current_password') || !Hash::check($request->current_password, $admin->password)) {
                return back()->withErrors(['current_password' => '現在のパスワードが正しくありません。']);
            }
            $admin->password = bcrypt($request->password);
        }

        $admin->email = $request->email;
        $admin->save();

        return back()->with('success', '更新しました。');
    }
}
