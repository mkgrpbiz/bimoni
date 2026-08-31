<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdAgencyShareUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdAgencyShareController extends Controller
{
    public function index(): View
    {
        $shareUsers = AdAgencyShareUser::orderByDesc('created_at')->get();
        $shareUrl = route('ad_agency_share.login');

        return view('admin.ad_agency_shares.index', compact('shareUsers', 'shareUrl'));
    }

    public function create(): View
    {
        return view('admin.ad_agency_shares.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:ad_agency_share_users,email',
            'password' => 'required|string|min:8',
        ]);

        AdAgencyShareUser::create($validated);

        return redirect()->route('admin.ad_agency_shares.index')->with('success', '共有アカウントを追加しました。');
    }

    public function destroy(AdAgencyShareUser $adAgencyShareUser): RedirectResponse
    {
        $adAgencyShareUser->delete();

        return redirect()->route('admin.ad_agency_shares.index')->with('success', '削除しました。');
    }
}
