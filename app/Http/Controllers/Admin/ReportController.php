<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Campaign;
use App\Models\MonitorReport;
use App\Services\LineMessagingService;
use App\Services\UserReferralService;
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

        // 「その他」報告を応募と紐付けるための候補（同じユーザーの応募。既に別の報告が紐付いている応募は
        // 選ぶと確実に重複になるため除外する）
        $linkedApplicationIds = MonitorReport::where('id', '!=', $report->id)
            ->whereNotNull('application_id')
            ->pluck('application_id');
        $linkableApplications = Application::with('campaign:id,title')
            ->where('user_id', $report->user_id)
            ->whereNotIn('id', $linkedApplicationIds)
            ->orderByDesc('applied_at')
            ->get();

        return view('admin.reports.show', compact('report', 'duplicates', 'campaigns', 'linkableApplications'));
    }

    public function approve(MonitorReport $report, UserReferralService $userReferralService): RedirectResponse
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

        $userReferralService->grantForApprovedReport($report->fresh('user'));

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

    public function updateCampaign(Request $request, MonitorReport $report, UserReferralService $userReferralService): RedirectResponse
    {
        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
        ]);

        $report->update(['campaign_id' => $request->campaign_id]);

        $userReferralService->grantForApprovedReport($report->fresh('user'));

        return $this->redirectWithDuplicateWarning($report->fresh(), '案件を変更しました。');
    }

    public function updatePurchaseType(Request $request, MonitorReport $report, UserReferralService $userReferralService): RedirectResponse
    {
        $request->validate([
            'purchase_type' => 'required|in:initial,continuation,other',
        ]);

        $report->update(['purchase_type' => $request->purchase_type]);

        // 「その他」で承認済みだった報告を後からinitialへ修正した場合など、
        // approve()時点では対象外だった報告がここで初めて招待報酬の対象になることがあるため再チェックする
        $userReferralService->grantForApprovedReport($report->fresh('user'));

        return $this->redirectWithDuplicateWarning($report->fresh(), '報告種別を変更しました。');
    }

    // 「その他」報告を、実際に応募済みのApplicationと紐付ける。
    // 案件変更・報告種別変更だけでは application_id が入らず、同じ応募に対する
    // 重複報告の検知（通常の応募経由フローが使う仕組み）が効かないまま二重支払いが起きたことがあったため、
    // 応募と紐付けた場合はここで確実に重複チェックし、campaign_id・bonus_amountも応募から引き継ぐ
    public function linkApplication(Request $request, MonitorReport $report, UserReferralService $userReferralService): RedirectResponse
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
        ]);

        $application = Application::where('id', $request->application_id)
            ->where('user_id', $report->user_id)
            ->first();

        if (!$application) {
            return back()->with('error', 'この応募は対象ユーザーのものではありません。');
        }

        // リンク元の報告はまだ「その他」のままのことが多く、その時点のpurchase_typeで比較しても
        // 意味がないため、応募に既に紐づいている初回/継続報告の有無そのものをチェックする
        $duplicate = MonitorReport::where('application_id', $application->id)
            ->where('id', '!=', $report->id)
            ->where('status', '!=', 'rejected')
            ->whereIn('purchase_type', ['initial', 'continuation'])
            ->first();

        if ($duplicate) {
            return back()->with('error', "この応募には既に報告（report_id={$duplicate->id}、{$duplicate->getStatusLabel()}）が紐付いています。重複の可能性があるため紐付けを中止しました。");
        }

        $report->update([
            'application_id' => $application->id,
            'campaign_id'    => $application->campaign_id,
            'bonus_amount'   => $report->bonus_amount ?? $application->bonus_amount,
        ]);

        $userReferralService->grantForApprovedReport($report->fresh('user'));

        return back()->with('success', '応募と紐付けました。');
    }

    // 案件変更・報告種別変更の直後に、同じユーザー・同じ案件・同じ報告種別で
    // 既に他の報告（却下済み以外）が無いか確認し、あれば警告を出す
    private function redirectWithDuplicateWarning(MonitorReport $report, string $successMessage): RedirectResponse
    {
        $redirect = back()->with('success', $successMessage);

        if (!$report->campaign_id || $report->purchase_type === 'other') {
            return $redirect;
        }

        $duplicate = MonitorReport::where('user_id', $report->user_id)
            ->where('campaign_id', $report->campaign_id)
            ->where('purchase_type', $report->purchase_type)
            ->where('id', '!=', $report->id)
            ->where('status', '!=', 'rejected')
            ->first();

        if ($duplicate) {
            $redirect->with('warning', "同じユーザー・案件・報告種別の報告が他にもあります（report_id={$duplicate->id}、ステータス: {$duplicate->getStatusLabel()}）。重複していないか下の「重複申請チェック」欄で必ず確認してください。");
        }

        return $redirect;
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
