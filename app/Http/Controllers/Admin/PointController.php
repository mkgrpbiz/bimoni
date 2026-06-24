<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonitorReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PointController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $query = MonitorReport::with(['user', 'campaign'])
            ->where('status', 'approved')
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);

        if ($request->filled('bimoni_user_id')) {
            $user = User::where('bimoni_user_id', strtoupper($request->bimoni_user_id))->first();
            $query->where('user_id', $user?->id ?? 0);
        }

        $reports = $query->orderBy('created_at')->get();

        $totalAmount  = $reports->sum(fn($r) => $r->campaign?->cooperation_fee ?? 0);
        $pendingAmount = $reports->where('payment_status', 'pending')->sum(fn($r) => $r->campaign?->cooperation_fee ?? 0);

        return view('admin.points.index', compact('reports', 'month', 'totalAmount', 'pendingAmount'));
    }

    public function markPaid(Request $request): RedirectResponse
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

        MonitorReport::where('status', 'approved')
            ->where('payment_status', 'pending')
            ->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()])
            ->update(['payment_status' => 'paid', 'paid_at' => now()]);

        return redirect()->route('admin.points.index', ['month' => $request->month])
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
            $rows[] = [
                $r->created_at->format('Y/m/d'),
                $r->user?->bimoni_user_id ?? '',
                $r->user?->name ?? '',
                $r->payment_status === 'paid' ? '支払済' : '支払待ち',
                $r->campaign?->title ?? '',
                $r->campaign?->cooperation_fee ?? 0,
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
