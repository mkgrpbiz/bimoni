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
        $query = Campaign::orderBy('sort_order')->orderBy('id');

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }
        if ($request->input('filled') === '1') {
            $query->where(function ($q) {
                $q->whereNotNull('cancellation_info')
                    ->orWhereNotNull('cancellation_phone')
                    ->orWhereNotNull('cancellation_mypage_url')
                    ->orWhereNotNull('cancellation_email');
            });
        } elseif ($request->input('filled') === '0') {
            $query->whereNull('cancellation_info')
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
            'cancellation_info'       => 'nullable|string',
            'cancellation_phone'      => 'nullable|string|max:50',
            'cancellation_hours'      => 'nullable|string|max:255',
            'cancellation_mypage_url' => 'nullable|url|max:500',
            'cancellation_email'      => 'nullable|email|max:255',
        ]);

        $campaign->update($validated);

        return redirect()->route('admin.cancellation_settings.index')
            ->with('success', '解約方法を更新しました。');
    }
}
