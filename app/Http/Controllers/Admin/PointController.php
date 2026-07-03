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
    public function index(Request $request): View
    {
        $calcFee = function ($r) {
            $fee = $r->purchase_amount ?? 0;
            if ($r->purchase_type !== 'continuation') {
                $fee += ($r->campaign?->cooperation_fee ?? 0);
            }
            return $fee + ($r->application?->bonus_amount ?? 0);
        };

        // 先月・当月ブロック
        $blocks = [];
        foreach ([Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->startOfMonth()] as $m) {
            $reports = MonitorReport::with(['campaign', 'application'])
                ->where('status', 'approved')
                ->whereBetween('created_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])
                ->get();

            $blocks[] = [
                'month'      => $m->copy(),
                'total'      => $reports->sum($calcFee),
                'count'      => $reports->count(),
                'hasPending' => $reports->contains('payment_status', 'pending'),
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

        $query = MonitorReport::with(['user', 'campaign', 'application'])
            ->where('status', 'approved')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);

        if ($request->filled('q')) {
            $q = $request->q;
            $userIds = User::where('bimoni_user_id', 'like', '%' . $q . '%')
                ->orWhere('line_display_name', 'like', '%' . $q . '%')
                ->orWhere('name', 'like', '%' . $q . '%')
                ->orWhere('name_kana', 'like', '%' . $q . '%')
                ->pluck('id');
            $query->whereIn('user_id', $userIds);
        }

        $reports = $query->get();

        $userSummary = $reports->groupBy('user_id')->map(fn($rows) => [
            'user'   => $rows->first()->user,
            'total'  => $rows->sum($calcFee),
            'count'  => $rows->count(),
            'status' => $rows->contains('payment_status', 'pending') ? 'pending' : 'reserved',
        ])->sortByDesc('total')->values();

        $totalAmount = $reports->sum($calcFee);

        $summaryUserIds = $userSummary->pluck('user.id')->filter();
        $collectionCounts = CollectionReport::whereIn('user_id', $summaryUserIds)
            ->where('status', 'approved')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw('user_id, count(*) as cnt')
            ->groupBy('user_id')
            ->pluck('cnt', 'user_id');

        return view('admin.points.index', compact(
            'blocks', 'month', 'year', 'mon', 'months', 'userSummary', 'totalAmount', 'collectionCounts'
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

        return redirect()->route('admin.points.index', ['year' => $month->year, 'month' => $month->month])
            ->with('success', $month->format('Y年n月') . 'の支払いを支払済みにしました。');
    }

    public function exportCsv(Request $request): Response
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

        $reports = MonitorReport::with(['user', 'campaign'])
            ->where('status', 'approved')
            ->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->orderBy('created_at')
            ->get();

        $rows   = [];
        $rows[] = ['日時', 'ユーザーID', 'ユーザー名', 'ステータス', 'モニター名', '協力金'];

        foreach ($reports as $r) {
            $fee = $r->purchase_amount ?? 0;
            if ($r->purchase_type !== 'continuation') {
                $fee += ($r->campaign?->cooperation_fee ?? 0);
            }
            $fee += ($r->application?->bonus_amount ?? 0);
            $rows[] = [
                $r->created_at->format('Y/m/d'),
                $r->user?->bimoni_user_id ?? '',
                $r->user?->name ?? '',
                $r->payment_status === 'paid' ? '支払済' : '支払待ち',
                $r->campaign?->title ?? '',
                $fee,
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
        $transferDate = Carbon::parse($request->transfer_date)->format('md'); // MMDD

        $reports = MonitorReport::with(['user', 'campaign', 'application'])
            ->where('status', 'approved')
            ->where('payment_status', 'pending')
            ->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->get();

        // ユーザーごとに協力金を合計
        $userTotals = [];
        foreach ($reports as $r) {
            $uid = $r->user_id;
            if (!isset($userTotals[$uid])) {
                $userTotals[$uid] = ['user' => $r->user, 'amount' => 0];
            }
            $rowFee = $r->purchase_amount ?? 0;
            if ($r->purchase_type !== 'continuation') {
                $rowFee += ($r->campaign?->cooperation_fee ?? 0);
            }
            $rowFee += ($r->application?->bonus_amount ?? 0);
            $userTotals[$uid]['amount'] += $rowFee;
        }

        // 口座情報未登録・金額ゼロを除外
        $userTotals = array_filter($userTotals, fn($u) =>
            $u['user']?->bank_code &&
            $u['user']?->bank_account_number &&
            $u['amount'] > 0
        );

        $totalCount  = count($userTotals);
        $totalAmount = array_sum(array_column($userTotals, 'amount'));

        $lines = [];

        // ヘッダーレコード
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

        // データレコード
        foreach ($userTotals as $data) {
            $user = $data['user'];
            // 全角カナ→半角カナ変換
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

        // トレーラーレコード
        $lines[] = implode(',', ['8', $totalCount, $totalAmount]);

        // エンドレコード
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

        // 手動調整は Point レコードに記録
        $user->points()->create([
            'type'       => 'adjust',
            'amount'     => $request->amount,
            'reason'     => $request->reason,
            'created_at' => now(),
        ]);

        return back()->with('success', "{$user->name} の手動調整（¥" . number_format($request->amount) . "）を記録しました。");
    }
}
