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

        $query = Campaign::when($visible === 'draft',
                fn ($q) => $q->where('cancellation_draft', true),
                fn ($q) => $q->where('cancellation_draft', false)->where('cancellation_visible', $visible === '1')
            )
            ->orderByRaw('CASE WHEN cancellation_method IS NULL AND cancellation_phone IS NULL AND cancellation_hours IS NULL AND cancellation_mypage_url IS NULL AND cancellation_email IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        $campaigns = $query->paginate(50)->withQueryString();

        $visibleCounts = Campaign::where('cancellation_draft', false)
            ->selectRaw('cancellation_visible, count(*) as count')
            ->groupBy('cancellation_visible')
            ->pluck('count', 'cancellation_visible');
        $draftCount = Campaign::where('cancellation_draft', true)->count();

        return view('admin.cancellation_settings.index', compact('campaigns', 'visible', 'visibleCounts', 'draftCount'));
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

    public function setVisible(Request $request, Campaign $campaign): RedirectResponse
    {
        $request->validate(['visible' => 'required|in:0,1']);

        // 表示/非表示を明示的に選ぶ操作なので、下書き中だった場合は下書きから抜ける
        $campaign->update([
            'cancellation_visible' => $request->visible === '1',
            'cancellation_draft'   => false,
        ]);

        return back()->with('success', '表示設定を変更しました。');
    }

    public function moveToDraft(Campaign $campaign): RedirectResponse
    {
        $campaign->update(['cancellation_draft' => true]);

        return back()->with('success', '下書きに移しました。');
    }
}
