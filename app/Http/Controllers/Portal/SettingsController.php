<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $agent = \App\Services\PortalService::agent();
        return view('portal.settings', compact('agent'));
    }

    public function update(\Illuminate\Http\Request $request)
    {
        $agent = \App\Services\PortalService::agent();
        $request->validate(['invite_display_name' => 'nullable|string|max:100']);
        $agent->update(['invite_display_name' => $request->invite_display_name ?: null]);
        return back()->with('success', '情報を更新しました。');
    }
}
