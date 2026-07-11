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
        $query = Campaign::orderByDesc('id');

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }
        if ($request->input('filled') === '1') {
            $query->where(function ($q) {
                $q->whereNotNull('cancellation_method')
                    ->orWhereNotNull('cancellation_phone')
                    ->orWhereNotNull('cancellation_mypage_url')
                    ->orWhereNotNull('cancellation_email');
            });
        } elseif ($request->input('filled') === '0') {
            $query->whereNull('cancellation_method')
                ->whereNull('cancellation_phone')
                ->whereNull('cancellation_mypage_url')
                ->whereNull('cancellation_email');
        }

        $campaigns = $query->paginate(50)->withQueryString();

        return view('admin.cancellation_settings.index', compact('campaigns'));
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

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->update([
            'cancellation_method'     => null,
            'cancellation_phone'      => null,
            'cancellation_hours'      => null,
            'cancellation_mypage_url' => null,
            'cancellation_email'      => null,
        ]);

        return redirect()->route('admin.cancellation_settings.index')
            ->with('success', '解約方法を削除しました。');
    }
}
