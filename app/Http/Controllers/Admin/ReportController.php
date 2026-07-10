<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\MonitorReport;
use App\Services\LineMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'pending');

        $query = MonitorReport::with(['user', 'campaign', 'images'])
            ->where('status', $status)
            ->latest();

        if ($request->filled('q')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('bimoni_user_id', 'like', '%' . $request->q . '%')
                  ->orWhere('line_display_name', 'like', '%' . $request->q . '%')
                  ->orWhere('name', 'like', '%' . $request->q . '%')
                  ->orWhere('name_kana', 'like', '%' . $request->q . '%');
            });
        }

        $reports = $query->paginate(20)->withQueryString();

        $counts = MonitorReport::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('admin.reports.index', compact('reports', 'status', 'counts'));
    }

    public function show(MonitorReport $report): View
    {
        $report->load(['user', 'campaign', 'application', 'images', 'reviewedBy']);

        $duplicates = $report->campaign_id
            ? MonitorReport::with('images')
                ->where('user_id', $report->user_id)
                ->where('campaign_id', $report->campaign_id)
                ->where('id', '!=', $report->id)
                ->latest()
                ->get()
            : collect();

        $campaigns = Campaign::orderBy('sort_order')->orderBy('id')->get(['id', 'title', 'status']);

        return view('admin.reports.show', compact('report', 'duplicates', 'campaigns'));
    }

    public function approve(MonitorReport $report): RedirectResponse
    {
        $report->update([
            'status'      => 'approved',
            'reviewed_by' => Auth::guard('web')->id(),
            'reviewed_at' => now(),
            'reject_reason' => null,
        ]);

        $report->application?->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        return back()->with('success', '報告を承認しました。');
    }

    public function reject(Request $request, MonitorReport $report, LineMessagingService $lineService): RedirectResponse
    {
        $request->validate(['reject_reason' => 'required|string|max:500']);

        $report->update([
            'status'        => 'rejected',
            'reject_reason' => $request->reject_reason,
            'reviewed_by'   => Auth::guard('web')->id(),
            'reviewed_at'   => now(),
        ]);

        $report->application?->update(['status' => 'reported']);

        $msg = "【モニター報告について】\n"
            . "差戻しとなりました。\n\n"
            . "理由：{$request->reject_reason}\n\n"
            . "お手数ですが、内容をご確認の上、再度報告フォームよりご報告ください。";

        $lineService->sendPush($report->user_id, $msg, 'report_rejection');

        return back()->with('success', '差戻し・LINE通知を送信しました。');
    }

    public function revert(MonitorReport $report): RedirectResponse
    {
        $report->update([
            'status'        => 'pending',
            'reviewed_by'   => null,
            'reviewed_at'   => null,
            'reject_reason' => null,
        ]);

        $report->application?->update(['status' => 'reported']);

        return back()->with('success', '承認待ちに戻しました。');
    }

    public function updateCampaign(Request $request, MonitorReport $report): RedirectResponse
    {
        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
        ]);

        $report->update(['campaign_id' => $request->campaign_id]);

        return back()->with('success', '案件を変更しました。');
    }

    public function updatePurchaseType(Request $request, MonitorReport $report): RedirectResponse
    {
        $request->validate([
            'purchase_type' => 'required|in:initial,continuation,other',
        ]);

        $report->update(['purchase_type' => $request->purchase_type]);

        return back()->with('success', '報告種別を変更しました。');
    }

    public function adjust(Request $request, MonitorReport $report): RedirectResponse
    {
        $request->validate([
            'adjustment_amount' => 'required|integer|not_in:0',
            'adjustment_reason' => 'required|string|max:255',
        ]);

        $report->update([
            'adjustment_amount' => $request->adjustment_amount,
            'adjustment_reason' => $request->adjustment_reason,
        ]);

        return back()->with('success', '金額を修正しました。');
    }
}
