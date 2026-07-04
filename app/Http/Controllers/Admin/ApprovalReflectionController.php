<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        // 月次: 選択月の1レコード / 累計: campaign_id ごとに合計
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
                ->groupBy('campaign_id')
                ->get()
                ->keyBy('campaign_id');
        }

        // 応募の実施完了数・承認済数 (期間フィルター)
        $applicationStats = \App\Models\Application::selectRaw('
                campaign_id,
                SUM(CASE WHEN status IN (\'completed\',\'reported\',\'approved\',\'point_granted\') THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status IN (\'approved\',\'point_granted\') THEN 1 ELSE 0 END) as approved_count
            ')
            ->when($mode === 'monthly', function ($q) use ($year, $month) {
                $q->whereYear('completed_at', $year)->whereMonth('completed_at', $month);
            })
            ->groupBy('campaign_id')
            ->get()
            ->keyBy('campaign_id');

        // 月一覧（セレクトボックス用）
        $months = $this->getAvailableMonths();

        return view('admin.approval_reflections.index', compact(
            'campaigns', 'reflections', 'applicationStats',
            'year', 'month', 'mode', 'months'
        ));
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
        ]);

        $reflection = CampaignApprovalReflection::firstOrCreate(
            [
                'campaign_id'  => $campaign->id,
                'period_year'  => $validated['year'],
                'period_month' => $validated['month'],
            ],
            ['reflection_count' => 0, 'updated_by' => Auth::id()]
        );

        $reflection->update([
            'is_all_denied' => !$reflection->is_all_denied,
            'updated_by'    => Auth::id(),
        ]);

        return response()->json(['is_all_denied' => $reflection->is_all_denied]);
    }

    private function getAvailableMonths(): array
    {
        return \App\Models\Application::whereNotNull('completed_at')
            ->selectRaw('YEAR(completed_at) as y, MONTH(completed_at) as m')
            ->groupBy('y', 'm')
            ->orderByDesc('y')->orderByDesc('m')
            ->get()
            ->map(fn($r) => ['year' => (int)$r->y, 'month' => (int)$r->m, 'label' => \Carbon\Carbon::createFromDate($r->y, $r->m, 1)->format('Y年n月')])
            ->toArray();
    }
}
