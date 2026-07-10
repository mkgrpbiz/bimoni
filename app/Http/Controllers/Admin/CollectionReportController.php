<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CollectionReport;
use App\Services\LineMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CollectionReportController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'pending');

        $query = CollectionReport::with('user')
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

        $reports = $query->paginate(30)->withQueryString();

        $counts = CollectionReport::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('admin.collection_reports.index', compact('reports', 'status', 'counts'));
    }

    public function show(CollectionReport $collectionReport): View
    {
        $collectionReport->load('user', 'reviewer');
        $campaigns = $collectionReport->campaigns();

        $currentIds = $collectionReport->campaign_ids ?? [];
        $duplicates = CollectionReport::where('user_id', $collectionReport->user_id)
            ->where('id', '!=', $collectionReport->id)
            ->get()
            ->filter(fn($cr) => count(array_intersect($cr->campaign_ids ?? [], $currentIds)) > 0)
            ->values();

        return view('admin.collection_reports.show', compact('collectionReport', 'campaigns', 'duplicates'));
    }

    public function approve(CollectionReport $collectionReport): RedirectResponse
    {
        $collectionReport->update([
            'status'      => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', '承認しました。');
    }

    public function reject(Request $request, CollectionReport $collectionReport, LineMessagingService $lineService): RedirectResponse
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $collectionReport->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by'      => Auth::id(),
            'reviewed_at'      => now(),
        ]);

        $msg = "【回収報告について】\n"
            . "差戻しとなりました。\n\n"
            . "理由：{$request->rejection_reason}\n\n"
            . "お手数ですが、内容をご確認の上、再度報告フォームよりご報告ください。";

        $lineService->sendPush($collectionReport->user_id, $msg, 'collection_rejection');

        return back()->with('success', '差戻し・LINE通知を送信しました。');
    }

    public function adjust(Request $request, CollectionReport $collectionReport): RedirectResponse
    {
        $request->validate([
            'adjustment_amount' => 'required|integer|not_in:0',
            'adjustment_reason' => 'required|string|max:255',
        ]);

        $collectionReport->update([
            'adjustment_amount' => $request->adjustment_amount,
            'adjustment_reason' => $request->adjustment_reason,
        ]);

        return back()->with('success', '金額を修正しました。');
    }

    public function revert(CollectionReport $collectionReport): RedirectResponse
    {
        $collectionReport->update([
            'status'           => 'pending',
            'reviewed_by'      => null,
            'reviewed_at'      => null,
            'rejection_reason' => null,
        ]);

        return back()->with('success', '承認待ちに戻しました。');
    }
}
