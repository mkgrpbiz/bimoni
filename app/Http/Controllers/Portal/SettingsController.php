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
        $request->validate(['name' => 'required|string|max:100']);
        $agent->update(['name' => $request->name]);
        return back()->with('success', '情報を更新しました。');
    }
}
