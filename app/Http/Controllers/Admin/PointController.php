<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CollectionReport;
use App\Models\MonitorReport;
use App\Models\User;
use App\Services\PointService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PointController extends Controller
{
    private function monitorFee(MonitorReport $r): int
    {
        return ($r->purchase_amount ?? 0)
            + ($r->campaign?->cooperation_fee ?? 0)
            + ($r->application?->bonus_amount ?? 0);
    }

    public function index(Request $request): View
    {
        // 先月・当月ブロック
        $blocks = [];
        foreach ([Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->startOfMonth()] as $m) {
            $start = $m->copy()->startOfMonth();
            $end   = $m->copy()->endOfMonth();

            $monitors = MonitorReport::with(['campaign', 'application'])
                ->where('status', 'approved')
                ->whereBetween('created_at', [$start, $end])
                ->get();

            $collectionFee = CollectionReport::where('status', 'approved')
                ->whereBetween('created_at', [$start, $end])
                ->sum('cooperation_fee');

            $hasPendingCollection = CollectionReport::where('status', 'approved')
                ->where('payment_status', 'pending')
                ->whereBetween('created_at', [$start, $end])
                ->exists();

            $blocks[] = [
                'month'      => $m->copy(),
                'total'      => $monitors->sum(fn($r) => $this->monitorFee($r)) + $collectionFee,
                'count'      => $monitors->count(),
                'hasPending' => $monitors->contains('payment_status', 'pending') || $hasPendingCollection,
            ];
        }

        // 月別詳細（フィルター対象月）
        $year  = (int)($request->input('year',  now()->year));
        $mon   = (int)($request->input('month', now()->month));
        $month = Carbon::createFromDate($year, $mon, 1)->startOfMonth();

        $months = MonitorReport::where('status', 'approved')
            ->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m')
            ->groupBy('y', 'm')
            ->orderByDesc('y')->orderByDesc('m')
            ->get()
            ->map(fn($r) => ['year' => (int)$r->y, 'month' => (int)$r->m, 'label' => Carbon::createFromDate($r->y, $r->m, 1)->format('Y年n月')])
            ->toArray();

        $monitorQuery = MonitorReport::with(['user', 'campaign', 'application'])
            ->where('status', 'approved')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);

        $collectionQuery = CollectionReport::with('user')
            ->where('status', 'approved')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);

        if ($request->filled('q')) {
            $q       = $request->q;
            $userIds = User::where('bimoni_user_id', 'like', '%' . $q . '%')
                ->orWhere('line_display_name', 'like', '%' . $q . '%')
                ->orWhere('name', 'like', '%' . $q . '%')
                ->orWhere('name_kana', 'like', '%' . $q . '%')
                ->pluck('id');
            $monitorQuery->whereIn('user_id', $userIds);
            $collectionQuery->whereIn('user_id', $userIds);
        }

        $monitorReports     = $monitorQuery->get();
        $collectionReports  = $collectionQuery->get();

        // ユーザー別集計
        $userMap = [];

        foreach ($monitorReports->groupBy('user_id') as $uid => $rows) {
            $userMap[$uid] = [
                'user'            => $rows->first()->user,
                'monitorTotal'    => $rows->sum(fn($r) => $this->monitorFee($r)),
                'monitorCount'    => $rows->count(),
                'collectionTotal' => 0,
                'collectionCount' => 0,
                'status'          => $rows->contains('payment_status', 'pending') ? 'pending' : 'reserved',
            ];
        }

        foreach ($collectionReports as $cr) {
            $uid = $cr->user_id;
            if (!isset($userMap[$uid])) {
                $userMap[$uid] = [
                    'user'            => $cr->user,
                    'monitorTotal'    => 0,
                    'monitorCount'    => 0,
                    'collectionTotal' => 0,
                    'collectionCount' => 0,
                    'status'          => 'reserved',
                ];
            }
            $userMap[$uid]['collectionTotal'] += $cr->cooperation_fee;
            $userMap[$uid]['collectionCount'] += 1;
            if ($cr->payment_status === 'pending') {
                $userMap[$uid]['status'] = 'pending';
            }
        }

        $userSummary = collect($userMap)
            ->map(fn($r) => array_merge($r, ['total' => $r['monitorTotal'] + $r['collectionTotal']]))
            ->sortByDesc('total')
            ->values();

        $totalAmount = $userSummary->sum('total');

        return view('admin.points.index', compact(
            'blocks', 'month', 'year', 'mon', 'months', 'userSummary', 'totalAmount'
        ));
    }

    public function grant(MonitorReport $report, PointService $pointService): RedirectResponse
    {
        $pointService->grantForReport($report);
        return back()->with('success', '協力金を付与しました。');
    }

    public function markReserved(Request $request): RedirectResponse
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

        MonitorReport::where('status', 'approved')
            ->where('payment_status', 'pending')
            ->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->update(['payment_status' => 'reserved']);

        CollectionReport::where('status', 'approved')
            ->where('payment_status', 'pending')
            ->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->update(['payment_status' => 'reserved']);

        return redirect()->route('admin.points.index', ['year' => $month->year, 'month' => $month->month])
            ->with('success', $month->format('Y年n月') . 'の支払いを予約済みにしました。');
    }

    public function markPaid(Request $request): RedirectResponse
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

        MonitorReport::where('status', 'approved')
            ->whereIn('payment_status', ['pending', 'reserved'])
            ->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->update(['payment_status' => 'paid', 'paid_at' => now()]);

        CollectionReport::where('status', 'approved')
            ->whereIn('payment_status', ['pending', 'reserved'])
            ->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->update(['payment_status' => 'paid', 'paid_at' => now()]);

        return redirect()->route('admin.points.index', ['year' => $month->year, 'month' => $month->month])
            ->with('success', $month->format('Y年n月') . 'の支払いを支払済みにしました。');
    }

    public function exportCsv(Request $request): Response
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

        $monitors = MonitorReport::with(['user', 'campaign'])
            ->where('status', 'approved')
            ->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->orderBy('created_at')
            ->get();

        $collections = CollectionReport::with('user')
            ->where('status', 'approved')
            ->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->orderBy('created_at')
            ->get();

        $rows   = [];
        $rows[] = ['日時', 'ユーザーID', 'ユーザー名', 'ステータス', '種別/案件名', '協力金'];

        foreach ($monitors as $r) {
            $fee = ($r->purchase_amount ?? 0) + ($r->campaign?->cooperation_fee ?? 0) + ($r->application?->bonus_amount ?? 0);
            $rows[] = [
                $r->created_at->format('Y/m/d'),
                $r->user?->bimoni_user_id ?? '',
                $r->user?->name ?? '',
                $r->payment_status === 'paid' ? '支払済' : '支払待ち',
                $r->campaign?->title ?? '',
                $fee,
            ];
        }

        foreach ($collections as $r) {
            $rows[] = [
                $r->created_at->format('Y/m/d'),
                $r->user?->bimoni_user_id ?? '',
                $r->user?->name ?? '',
                $r->payment_status === 'paid' ? '支払済' : '支払待ち',
                '【回収】' . $r->item_count . '点',
                $r->cooperation_fee,
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\r\n";
        }

        $filename = '協力金_' . $month->format('Ym') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportZengin(Request $request): Response
    {
        $request->validate([
            'month'         => 'required|date_format:Y-m',
            'transfer_date' => 'required|date',
        ]);

        $month        = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $transferDate = Carbon::parse($request->transfer_date)->format('md');

        $monitors = MonitorReport::with(['user', 'campaign', 'application'])
            ->where('status', 'approved')
            ->where('payment_status', 'pending')
            ->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->get();

        $collections = CollectionReport::with('user')
            ->where('status', 'approved')
            ->where('payment_status', 'pending')
            ->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->get();

        $userTotals = [];

        foreach ($monitors as $r) {
            $uid = $r->user_id;
            if (!isset($userTotals[$uid])) {
                $userTotals[$uid] = ['user' => $r->user, 'amount' => 0];
            }
            $userTotals[$uid]['amount'] += ($r->purchase_amount ?? 0)
                + ($r->campaign?->cooperation_fee ?? 0)
                + ($r->application?->bonus_amount ?? 0);
        }

        foreach ($collections as $r) {
            $uid = $r->user_id;
            if (!isset($userTotals[$uid])) {
                $userTotals[$uid] = ['user' => $r->user, 'amount' => 0];
            }
            $userTotals[$uid]['amount'] += $r->cooperation_fee;
        }

        $userTotals = array_filter($userTotals, fn($u) =>
            $u['user']?->bank_code &&
            $u['user']?->bank_account_number &&
            $u['amount'] > 0
        );

        $totalCount  = count($userTotals);
        $totalAmount = array_sum(array_column($userTotals, 'amount'));

        $lines = [];

        $lines[] = implode(',', [
            '1', '21', '0',
            env('ZENGIN_CLIENT_CODE', '2017496001'),
            env('ZENGIN_CLIENT_NAME', 'BIMONI'),
            $transferDate,
            env('ZENGIN_BANK_CODE', '0038'), '',
            env('ZENGIN_BRANCH_CODE', '106'), '',
            env('ZENGIN_ACCOUNT_TYPE', '1'),
            env('ZENGIN_ACCOUNT_NUMBER', '2965755'),
        ]);

        foreach ($userTotals as $data) {
            $user = $data['user'];
            $name = mb_convert_kana($user->bank_account_name ?? '', 'k', 'UTF-8');

            $lines[] = implode(',', [
                '2',
                $user->bank_code ?? '',
                '',
                $user->bank_branch_code ?? '',
                '',
                '0000',
                $user->bank_account_type ?? '1',
                $user->bank_account_number ?? '',
                $name,
                $data['amount'],
                '', '',
                '7',
                '',
            ]);
        }

        $lines[] = implode(',', ['8', $totalCount, $totalAmount]);
        $lines[] = '9';

        $content  = implode("\r\n", $lines);
        $content  = mb_convert_encoding($content, 'SJIS-win', 'UTF-8');
        $filename = 'bimoni_' . $transferDate . '.csv';

        return response($content, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $request->validate([
            'bimoni_user_id' => 'required|string',
            'amount'         => 'required|integer|not_in:0',
            'reason'         => 'required|string|max:255',
        ]);

        $user = User::where('bimoni_user_id', strtoupper($request->bimoni_user_id))->first();
        if (!$user) {
            return back()->withErrors(['bimoni_user_id' => 'ユーザーIDが見つかりません。'])->withInput();
        }

        $user->points()->create([
            'type'       => 'adjust',
            'amount'     => $request->amount,
            'reason'     => $request->reason,
            'created_at' => now(),
        ]);

        return back()->with('success', "{$user->name} の手動調整（¥" . number_format($request->amount) . "）を記録しました。");
    }
}
