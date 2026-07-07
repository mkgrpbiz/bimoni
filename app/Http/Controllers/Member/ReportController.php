<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Campaign;
use App\Models\CollectionReport;
use App\Models\MonitorReport;
use App\Models\MonitorReportImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function show(MonitorReport $report): View|RedirectResponse
    {
        $user = Auth::guard('liff')->user();
        if ($report->user_id !== $user->id) {
            abort(403);
        }
        $report->load(['campaign', 'images']);
        return view('member.reports.show', compact('report'));
    }

    public function create(Request $request): View
    {
        $user = Auth::guard('liff')->user();

        // 全完了済み応募
        $allCompleted = Application::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'reported', 'approved'])
            ->with('campaign')
            ->get()
            ->filter(fn($a) => $a->campaign !== null);

        // モニター報告用: 購入タイプ別に申請済みを除外（初回・継続は独立して保持）
        $reportedInitialIds = MonitorReport::where('user_id', $user->id)
            ->where('purchase_type', 'initial')
            ->where('status', '!=', 'rejected')
            ->pluck('application_id')->all();
        $reportedContinuationIds = MonitorReport::where('user_id', $user->id)
            ->where('purchase_type', 'continuation')
            ->where('status', '!=', 'rejected')
            ->pluck('application_id')->all();

        $monitorInitialApps      = $allCompleted->whereNotIn('id', $reportedInitialIds)->values();
        $monitorContinuationApps = $allCompleted
            ->whereNotIn('id', $reportedContinuationIds)
            ->values();

        // 回収サービス用
        $initialApplications      = $allCompleted->values();
        $continuationApplications = $allCompleted->values();

        $reportType = $request->input('report_type', 'monitor');

        return view('member.reports.create', compact(
            'monitorInitialApps', 'monitorContinuationApps',
            'initialApplications', 'continuationApplications', 'reportType'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::guard('liff')->user();

        $isOther = $request->input('purchase_type') === 'other';

        $rules = [
            'purchase_type'    => 'required|in:initial,continuation,other',
            'report_image_1'   => 'required|image|max:10240',
            'report_image_2'   => 'nullable|image|max:10240',
            'report_image_3'   => 'nullable|image|max:10240',
        ];

        if ($isOther) {
            $rules['report_body'] = 'required|string|max:2000';
        } else {
            $rules['application_id']       = 'required|exists:applications,id';
            $rules['purchase_amount']      = 'required|integer|min:0';
            $rules['payment_method']       = 'required|string|max:50';
            $rules['payment_method_other'] = 'nullable|string|max:100';
            $rules['report_image_2']       = 'required|image|max:10240';
        }

        $request->validate($rules);

        if ($isOther) {
            $report = MonitorReport::create([
                'user_id'          => $user->id,
                'purchase_type'    => 'other',
                'report_body'      => $request->report_body,
                'purchase_amount'  => $request->purchase_amount ?? 0,
                'payment_method'   => $request->payment_method === 'other'
                                        ? 'other:' . $request->payment_method_other
                                        : $request->payment_method,
                'status'           => 'pending',
            ]);

            foreach (['report_image_1', 'report_image_2', 'report_image_3'] as $i => $key) {
                if ($request->hasFile($key) && $request->file($key)->isValid()) {
                    $path = $request->file($key)->store('reports', 'public');
                    MonitorReportImage::create([
                        'monitor_report_id' => $report->id,
                        'image_path'        => $path,
                        'sort_order'        => $i,
                    ]);
                }
            }

            return redirect()->route('member.mypage')->with('success', 'その他報告を送信しました。確認をお待ちください。');
        }

        $application = Application::where('id', $request->application_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (MonitorReport::where('application_id', $application->id)
            ->where('purchase_type', $request->purchase_type)
            ->where('status', '!=', 'rejected')
            ->exists()) {
            return back()->with('error', 'この案件のこの種類の報告は既に送信済みです。');
        }

        $report = MonitorReport::create([
            'user_id'              => $user->id,
            'campaign_id'          => $application->campaign_id,
            'application_id'       => $application->id,
            'purchase_type'        => $request->purchase_type,
            'purchase_amount'      => $request->purchase_amount,
            'bonus_amount'         => $application->bonus_amount,
            'payment_method'       => $request->payment_method === 'other'
                                        ? 'other:' . $request->payment_method_other
                                        : $request->payment_method,
            'status'               => 'pending',
        ]);

        foreach (['report_image_1', 'report_image_2', 'report_image_3'] as $i => $key) {
            if ($request->hasFile($key) && $request->file($key)->isValid()) {
                $path = $request->file($key)->store('reports', 'public');
                MonitorReportImage::create([
                    'monitor_report_id' => $report->id,
                    'image_path'        => $path,
                    'sort_order'        => $i,
                ]);
            }
        }

        $application->update(['status' => 'reported', 'reported_at' => now()]);

        return redirect()->route('member.mypage')->with('success', '報告を送信しました。審査をお待ちください。');
    }

    public function storeCollection(Request $request): RedirectResponse
    {
        $user = Auth::guard('liff')->user();

        $request->validate([
            'initial_app_ids'         => 'nullable|array',
            'initial_app_ids.*'       => 'integer|exists:applications,id',
            'continuation_app_ids'    => 'nullable|array',
            'continuation_app_ids.*'  => 'integer|exists:applications,id',
            'box_image'               => 'required|image|max:10240',
            'label_image'             => 'required|image|max:10240',
            'estimated_arrival_date'  => 'required|date|after:today',
            'tracking_number'         => 'required|digits_between:1,30',
            'shipping_fee'            => 'required|integer|min:0',
        ]);

        $initialIds      = $request->initial_app_ids ?? [];
        $continuationIds = $request->continuation_app_ids ?? [];

        if (empty($initialIds) && empty($continuationIds)) {
            return back()->withErrors(['initial_app_ids' => '返送する商品を1つ以上選択してください。'])->withInput();
        }

        $shippingFee = (int) $request->shipping_fee;

        $allAppIds    = array_merge($initialIds, $continuationIds);
        $applications = Application::whereIn('id', $allAppIds)
            ->where('user_id', $user->id)
            ->with('campaign')
            ->get()
            ->keyBy('id');

        $grossFee    = 0;
        $campaignIds = [];
        $itemCount   = 0;

        foreach ($initialIds as $appId) {
            $app = $applications->get($appId);
            if (!$app) continue;
            $grossFee += 800;
            $campaignIds[] = $app->campaign_id;
            $itemCount++;
        }
        foreach ($continuationIds as $appId) {
            $app = $applications->get($appId);
            if (!$app) continue;
            $count      = (int) ($app->campaign?->collection_count_judgment ?? 1);
            $grossFee  += 800 * $count;
            $itemCount += $count;
            $campaignIds[] = $app->campaign_id;
        }

        $fee = CollectionReport::calcFee($itemCount, $shippingFee);

        $boxPath   = $request->file('box_image')->store('collection', 'public');
        $labelPath = $request->file('label_image')->store('collection', 'public');

        CollectionReport::create([
            'user_id'                => $user->id,
            'campaign_ids'           => $campaignIds,
            'box_image'              => $boxPath,
            'label_image'            => $labelPath,
            'tracking_number'        => $request->tracking_number,
            'shipping_fee'           => $shippingFee,
            'estimated_arrival_date' => $request->estimated_arrival_date,
            'item_count'             => $itemCount,
            'cooperation_fee'        => $fee,
            'status'                 => 'pending',
        ]);

        return redirect()->route('member.mypage')->with('success', '回収サービスの報告を送信しました。確認後、協力金に反映されます。');
    }
}
