<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Application::with(['user', 'campaign'])->latest('applied_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }
        if ($request->filled('q')) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', '%'.$request->q.'%'));
        }

        $applications = $query->paginate(30)->withQueryString();
        $campaigns = Campaign::orderBy('title')->get();

        return view('admin.applications.index', compact('applications', 'campaigns'));
    }

    public function campaignIndex(Campaign $campaign, Request $request): View
    {
        $query = $campaign->applications()->with('user')->latest('applied_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->paginate(30)->withQueryString();

        return view('admin.applications.campaign_index', compact('campaign', 'applications'));
    }

    public function show(Application $application): View
    {
        $application->load(['user', 'campaign', 'schedules.proposedBy']);
        return view('admin.applications.show', compact('application'));
    }

    public function updateStatus(Request $request, Application $application): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:selected,rejected,line_contacted,scheduled,completed,cancelled',
        ]);

        $data = ['status' => $request->status];

        if ($request->status === 'selected') {
            $data['selected_at'] = now();
        } elseif ($request->status === 'line_contacted') {
            $data['line_contacted_at'] = now();
            $data['line_contact_status'] = 'sent';
        }

        $application->update($data);

        return back()->with('success', 'ステータスを更新しました。');
    }

    public function updateNotes(Request $request, Application $application): RedirectResponse
    {
        $request->validate(['notes' => 'nullable|string']);
        $application->update(['notes' => $request->notes]);
        return back()->with('success', 'メモを保存しました。');
    }
}
