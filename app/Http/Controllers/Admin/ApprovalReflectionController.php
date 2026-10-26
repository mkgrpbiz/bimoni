<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignApprovalReflection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalReflectionController extends Controller
{
    public function index(Request $request)
    {
        $year  = (int)($request->input('year',  now()->year));
        $month = (int)($request->input('month', now()->month));
        $mode  = $request->input('mode', 'monthly'); // monthly or cumulative

        $campaigns = Campaign::orderBy('sort_order')->orderBy('id')->get();

        // 月次: 選択月のデータ
        // 2026-02より前を除外するSQL条件
        $excludePeriodSql = "(period_year > 2026 OR (period_year = 2026 AND period_month >= 2))";
        $excludeDateSql   = "completed_at >= '2026-02-01'";

        // 月次: 選択月の1レコード / 累計: campaign_id ごとに合計（旧体制期間除外）
        if ($mode === 'monthly') {
            $reflections = CampaignApprovalReflection::where('period_year', $year)
                ->where('period_month', $month)
                ->get()
                ->keyBy('campaign_id');
        } else {
            $reflections = CampaignApprovalReflection::selectRaw(
                    'campaign_id,
                     SUM(reflection_count) as reflection_count,
                     MAX(is_all_denied) as is_all_denied'
                )
                ->whereRaw($excludePeriodSql)
                ->groupBy('campaign_id')
                ->get()
                ->keyBy('campaign_id');
        }

        // 応募の実施完了数・承認済数 (期間フィルター・旧体制期間除外)
        $applicationStats = \App\Models\Application::selectRaw('
                campaign_id,
                SUM(CASE WHEN status IN (\'completed\',\'reported\',\'approved\',\'point_granted\') THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status IN (\'approved\',\'point_granted\') THEN 1 ELSE 0 END) as approved_count
            ')
            ->when($mode === 'monthly', function ($q) use ($year, $month) {
                $q->whereYear('completed_at', $year)->whereMonth('completed_at', $month);
            })
            ->when($mode !== 'monthly', function ($q) use ($excludeDateSql) {
                $q->whereRaw($excludeDateSql);
            })
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');

        // 全否認キャンペーンID。is_all_deniedは月ごとの実績フラグなので、月次表示では選択中の月だけに
        // 絞る（他の月まで巻き込むと表示・トグルの両方でおかしくなる。2026-09-29修正）。
        // 累計表示は期間全体のどこかで全否認があった案件を示す一覧なので従来通り期間内のどこかでtrueなら対象
        if ($mode === 'monthly') {
            $allDeniedCampaignIds = CampaignApprovalReflection::where('is_all_denied', true)
                ->where('period_year', $year)->where('period_month', $month)
                ->pluck('campaign_id')->unique();
        } else {
            $allDeniedCampaignIds = CampaignApprovalReflection::where('is_all_denied', true)
                ->whereRaw($excludePeriodSql)
                ->pluck('campaign_id')->unique();
        }

        // 月一覧（セレクトボックス用）
        $months = $this->getAvailableMonths();

        // 「案件を追加」用の候補（実施0件で一覧に出てこない案件を、締め日の都合で手動追加できるようにする）。
        // 月次モードのみ対象（累計はある月に追加されたレコードが自動で合算されるため追加操作自体が不要）。
        // 候補は、今の一覧条件（実施1件以上 or 反映レコードあり）で既に表示されている案件を除き、
        // ステータスが「案内終了」に変わった日が2ヶ月以内のものだけに絞る（翌月末締めの運用上、
        // それより古い案件を足す必要はないため）。「終了日」専用のカラムが無いため、監査ログ
        // （AuditLog、2026-08-31以降の管理画面操作のみ記録）からstatus→closedへの最新の変更日時を使う
        $addableCampaigns = collect();
        if ($mode === 'monthly') {
            $visibleCampaignIds = $campaigns->filter(function ($c) use ($applicationStats, $reflections) {
                $completedCount = $applicationStats->get($c->id)?->completed_count ?? 0;
                return $completedCount >= 1 || $reflections->has($c->id);
            })->pluck('id');

            $recentlyClosedCampaignIds = AuditLog::where('model', Campaign::class)
                ->where('action', 'updated')
                ->where('changes->status->to', 'closed')
                ->where('created_at', '>=', now()->subMonths(2))
                ->pluck('model_id')
                ->unique();

            $addableCampaigns = Campaign::whereNotIn('id', $visibleCampaignIds)
                ->where('status', 'closed')
                ->whereIn('id', $recentlyClosedCampaignIds)
                ->orderBy('sort_order')->orderBy('id')
                ->get(['id', 'title']);
        }

        return view('admin.approval_reflections.index', compact(
            'campaigns', 'reflections', 'applicationStats',
            'year', 'month', 'mode', 'months', 'allDeniedCampaignIds', 'addableCampaigns'
        ));
    }

    // 締め日の都合で実施0件の月にも案件を一覧に出したい時に使う。
    // 空の反映レコード（反映数0）を作るだけで、一覧の表示条件（実施1件以上 or 反映レコードあり）に
    // より次回以降もその月は表示され続ける
    public function addCampaign(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'year'  => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        CampaignApprovalReflection::firstOrCreate(
            ['campaign_id' => $campaign->id, 'period_year' => $validated['year'], 'period_month' => $validated['month']],
            ['reflection_count' => 0, 'updated_by' => Auth::id()]
        );

        return back()->with('success', '案件を追加しました。');
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'year'             => 'required|integer|min:2020|max:2099',
            'month'            => 'required|integer|min:1|max:12',
            'reflection_count' => 'required|integer|min:0',
        ]);

        CampaignApprovalReflection::updateOrCreate(
            [
                'campaign_id'  => $campaign->id,
                'period_year'  => $validated['year'],
                'period_month' => $validated['month'],
            ],
            [
                'reflection_count' => $validated['reflection_count'],
                'updated_by'       => Auth::id(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function toggleAllDenied(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'year'  => 'required|integer',
            'month' => 'required|integer',
            'mode'  => 'nullable|string',
        ]);

        // 全否認は月ごとの実績フラグ（campaign_id + period単位）。以前はキャンペーン単位で全期間を
        // 一括更新していたが、ある月だけ全否認だった案件のフラグが他の月の報告まで巻き込んで
        // 紹介報酬・ダッシュボードの集計を狂わせるバグの原因になっていたため、指定した月だけを
        // 更新するように修正（2026-09-29）。継続して全否認の月は都度トグルする運用に変更
        $reflection = CampaignApprovalReflection::firstOrCreate(
            ['campaign_id' => $campaign->id, 'period_year' => $validated['year'], 'period_month' => $validated['month']],
            ['reflection_count' => 0, 'updated_by' => Auth::id()]
        );

        $newVal = !$reflection->is_all_denied;
        $reflection->update(['is_all_denied' => $newVal, 'updated_by' => Auth::id()]);

        return response()->json(['is_all_denied' => $newVal]);
    }

    private function getAvailableMonths(): array
    {
        $months = \App\Models\Application::whereNotNull('completed_at')
            ->whereRaw("completed_at >= '2026-02-01'")
            ->selectRaw('YEAR(completed_at) as y, MONTH(completed_at) as m')
            ->groupBy('y', 'm')
            ->orderByDesc('y')->orderByDesc('m')
            ->get()
            ->map(fn($r) => ['year' => (int)$r->y, 'month' => (int)$r->m, 'label' => \Carbon\Carbon::createFromDate($r->y, $r->m, 1)->format('Y年n月')])
            ->toArray();

        // 当月がまだ実施完了0件でも選べるよう、含まれていなければ先頭に追加
        $nowY = now()->year;
        $nowM = now()->month;
        if (!collect($months)->contains(fn($m) => $m['year'] === $nowY && $m['month'] === $nowM)) {
            array_unshift($months, ['year' => $nowY, 'month' => $nowM, 'label' => now()->format('Y年n月')]);
        }

        return $months;
    }
}
