<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
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
        $coopFee = $r->purchase_type === 'continuation'
            ? ($r->campaign?->continuation_cooperation_fee ?? 0)
            : ($r->campaign?->cooperation_fee ?? 0);
        return ($r->purchase_amount ?? 0) + $coopFee + ($r->bonus_amount ?? 0) + ($r->adjustment_amount ?? 0);
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
                ->get()
                ->sum(fn($r) => $r->totalFee());

            $hasPendingCollection = CollectionReport::where('status', 'approved')
                ->where('payment_status', 'pending')
                ->whereBetween('created_at', [$start, $end])
                ->exists();

            $hasPending = $monitors->contains('payment_status', 'pending') || $hasPendingCollection;
            $allPaid    = $monitors->count() > 0
                && $monitors->every(fn($r) => $r->payment_status === 'paid')
                && !$hasPendingCollection;

            $blocks[] = [
                'month'      => $m->copy(),
                'total'      => $monitors->sum(fn($r) => $this->monitorFee($r)) + $collectionFee,
                'count'      => $monitors->count(),
                'hasPending' => $hasPending,
                'allPaid'    => $allPaid,
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
            if ($rows->contains('payment_status', 'pending')) {
                $status = 'pending';
            } elseif ($rows->every(fn($r) => $r->payment_status === 'paid')) {
                $status = 'paid';
            } else {
                $status = 'reserved';
            }
            $userMap[$uid] = [
                'user'            => $rows->first()->user,
                'monitorTotal'    => $rows->sum(fn($r) => $this->monitorFee($r)),
                'monitorCount'    => $rows->count(),
                'collectionTotal' => 0,
                'collectionCount' => 0,
                'status'          => $status,
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
                    'status'          => $cr->payment_status === 'paid' ? 'paid' : 'reserved',
                ];
            }
            $userMap[$uid]['collectionTotal'] += $cr->totalFee();
            $userMap[$uid]['collectionCount'] += 1;
            if ($cr->payment_status === 'pending') {
                $userMap[$uid]['status'] = 'pending';
            } elseif ($cr->payment_status !== 'paid' && $userMap[$uid]['status'] === 'paid') {
                $userMap[$uid]['status'] = 'reserved';
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

        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();

        MonitorReport::where('status', 'approved')
            ->where('payment_status', 'pending')
            ->whereBetween('created_at', [$start, $end])
            ->update(['payment_status' => 'reserved']);

        CollectionReport::where('status', 'approved')
            ->where('payment_status', 'pending')
            ->whereBetween('created_at', [$start, $end])
            ->update(['payment_status' => 'reserved']);

        return redirect()->route('admin.points.index', ['year' => $month->year, 'month' => $month->month])
            ->with('success', $month->format('Y年n月') . 'の支払いを予約済みにしました。');
    }

    public function markPaid(Request $request): RedirectResponse
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();

        $reports = MonitorReport::where('status', 'approved')
            ->whereIn('payment_status', ['pending', 'reserved'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        MonitorReport::whereIn('id', $reports->pluck('id'))
            ->update(['payment_status' => 'paid', 'paid_at' => now()]);

        // 紐づく応募を point_granted に更新
        $applicationIds = $reports->pluck('application_id')->filter();
        Application::whereIn('id', $applicationIds)
            ->where('status', 'approved')
            ->update(['status' => 'point_granted']);

        CollectionReport::where('status', 'approved')
            ->whereIn('payment_status', ['pending', 'reserved'])
            ->whereBetween('created_at', [$start, $end])
            ->update(['payment_status' => 'paid', 'paid_at' => now()]);

        return redirect()->route('admin.points.index', ['year' => $month->year, 'month' => $month->month])
            ->with('success', $month->format('Y年n月') . 'の支払いを支払済みにしました。');
    }

    public function exportZengin(Request $request): Response
    {
        $request->validate([
            'month'         => 'required|date_format:Y-m',
            'transfer_date' => 'required|date',
        ]);

        $month        = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $transferDate = Carbon::parse($request->transfer_date)->format('md');

        $zenginStart = $month->copy()->startOfMonth();
        $zenginEnd   = $month->copy()->endOfMonth();

        $monitors = MonitorReport::with(['user', 'campaign'])
            ->where('status', 'approved')
            ->where('payment_status', 'pending')
            ->whereBetween('created_at', [$zenginStart, $zenginEnd])
            ->get();

        $collections = CollectionReport::with('user')
            ->where('status', 'approved')
            ->where('payment_status', 'pending')
            ->whereBetween('created_at', [$zenginStart, $zenginEnd])
            ->get();

        // 振込先（銀行コード+支店コード+口座番号+口座名義）単位で集約する。
        // 元スプレッドシート運用（GASマクロ）と同じ集約キー・正規化ロジックに揃えている。
        $recipients = [];

        $accumulate = function ($user, int $amount) use (&$recipients) {
            if (!$user || !$user->bank_code || !$user->bank_account_number || $amount <= 0) {
                return;
            }
            $bankCode   = str_pad($this->digitsOnly($user->bank_code), 4, '0', STR_PAD_LEFT);
            $branchCode = str_pad($this->digitsOnly($user->bank_branch_code), 3, '0', STR_PAD_LEFT);
            $account    = str_pad($this->digitsOnly($user->bank_account_number), 7, '0', STR_PAD_LEFT);
            $name       = $this->normalizeRecipientName($user->bank_account_name);
            if ($name === '') {
                return;
            }

            $key = $bankCode . '|' . $branchCode . '|' . $account . '|' . $name;
            if (!isset($recipients[$key])) {
                $recipients[$key] = [
                    'bankCode'   => $bankCode,
                    'branchCode' => $branchCode,
                    'account'    => $account,
                    'name'       => $name,
                    'amount'     => 0,
                ];
            }
            $recipients[$key]['amount'] += $amount;
        };

        foreach ($monitors as $r) {
            $coopFee = $r->purchase_type === 'continuation'
                ? ($r->campaign?->continuation_cooperation_fee ?? 0)
                : ($r->campaign?->cooperation_fee ?? 0);
            $accumulate($r->user, ($r->purchase_amount ?? 0) + $coopFee + ($r->bonus_amount ?? 0) + ($r->adjustment_amount ?? 0));
        }

        foreach ($collections as $r) {
            $accumulate($r->user, $r->totalFee());
        }

        $totalCount  = count($recipients);
        $totalAmount = array_sum(array_column($recipients, 'amount'));

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

        foreach ($recipients as $data) {
            $lines[] = implode(',', [
                '2',
                $data['bankCode'],
                '',
                $data['branchCode'],
                '',
                '0000',
                '1',
                $data['account'],
                $data['name'],
                (string) (int) $data['amount'],
                '', '',
                '7',
                '',
            ]);
        }

        $lines[] = implode(',', ['8', $totalCount, (string) (int) $totalAmount]);
        $lines[] = '9';

        $content  = implode("\r\n", $lines);
        $content  = mb_convert_encoding($content, 'SJIS-win', 'UTF-8');
        $filename = 'bimoni_' . $transferDate . '.csv';

        return response($content, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function digitsOnly(?string $value): string
    {
        return preg_replace('/\D/', '', $value ?? '') ?? '';
    }

    // スプレッドシート運用時に実際に使っていたGASマクロ（normalizeRecipientName）と同じ正規化ロジック
    private function normalizeRecipientName(?string $value): string
    {
        $s = trim($value ?? '');

        // スペース除去
        $s = str_replace(['　', ' '], '', $s);

        // 全角英数字 → 半角（記号は対象外。mb_convert_kanaの'a'は記号も変換してしまうため使わない）
        $s = preg_replace_callback('/[\x{FF21}-\x{FF3A}\x{FF41}-\x{FF5A}\x{FF10}-\x{FF19}]/u', function ($m) {
            return mb_chr(mb_ord($m[0], 'UTF-8') - 0xFEE0, 'UTF-8');
        }, $s);

        // よくある記号を銀行向けに寄せる
        $s = str_replace(
            ['‐', '‑', '‒', '–', '—', '―', 'ー', '－', '−', '・', '（', '）', '．', '／'],
            ['ｰ', 'ｰ', 'ｰ', 'ｰ', 'ｰ', 'ｰ', 'ｰ', 'ｰ', 'ｰ', '･', '(', ')', '.', '/'],
            $s
        );

        // 全角カナ → 半角カナ
        $s = mb_convert_kana($s, 'k', 'UTF-8');

        // 銀行向けに許容しやすい文字だけ残す
        return preg_replace('/[^0-9A-Za-z\x{FF66}-\x{FF9F}().\/-]/u', '', $s) ?? '';
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
