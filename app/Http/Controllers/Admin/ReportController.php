<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonitorReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = MonitorReport::with(['user', 'campaign'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->paginate(20)->withQueryString();

        return view('admin.reports.index', compact('reports'));
    }

    public function show(MonitorReport $report): View
    {
        $report->load(['user', 'campaign', 'application', 'images', 'reviewedBy']);
        return view('admin.reports.show', compact('report'));
    }

    public function approve(MonitorReport $report): RedirectResponse
    {
        $report->update([
            'status'      => 'approved',
            'reviewed_by' => Auth::guard('web')->id(),
            'reviewed_at' => now(),
            'reject_reason' => null,
        ]);

        $report->application->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        return back()->with('success', '報告を承認しました。');
    }

    public function reject(Request $request, MonitorReport $report): RedirectResponse
    {
        $request->validate(['reject_reason' => 'required|string']);

        $report->update([
            'status'        => 'rejected',
            'reviewed_by'   => Auth::guard('web')->id(),
            'reviewed_at'   => now(),
            'reject_reason' => $request->reject_reason,
        ]);

        $report->application->update(['status' => 'reported']);

        return back()->with('success', '差戻しました。');
    }
}
