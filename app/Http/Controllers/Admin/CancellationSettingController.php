<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CancellationSettingController extends Controller
{
    public function index(Request $request): View
    {
        $visible = $request->input('visible', '1');

        $query = Campaign::orderByDesc('id')->where('cancellation_visible', $visible === '1');

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        $campaigns = $query->paginate(50)->withQueryString();

        $visibleCounts = Campaign::selectRaw('cancellation_visible, count(*) as count')
            ->groupBy('cancellation_visible')
            ->pluck('count', 'cancellation_visible');

        return view('admin.cancellation_settings.index', compact('campaigns', 'visible', 'visibleCounts'));
    }

    public function edit(Campaign $campaign): View
    {
        return view('admin.cancellation_settings.edit', compact('campaign'));
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'cancellation_method'     => 'nullable|string',
            'cancellation_phone'      => 'nullable|string|max:50',
            'cancellation_hours'      => 'nullable|string|max:255',
            'cancellation_mypage_url' => 'nullable|url|max:500',
            'cancellation_email'      => 'nullable|email|max:255',
        ]);

        $campaign->update($validated);

        return redirect()->route('admin.cancellation_settings.index')
            ->with('success', '解約方法を更新しました。');
    }

    public function toggleVisible(Campaign $campaign): RedirectResponse
    {
        $campaign->update(['cancellation_visible' => !$campaign->cancellation_visible]);

        return back()->with('success', '表示設定を変更しました。');
    }
}
