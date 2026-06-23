<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Point;
use App\Models\PointSettlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettlementController extends Controller
{
    public function index(): View
    {
        $settlements = PointSettlement::latest('settlement_month')->paginate(12);
        $pendingCount = Application::where('status', 'approved')->count();

        return view('admin.settlements.index', compact('settlements', 'pendingCount'));
    }

    public function show(PointSettlement $settlement): View
    {
        $settlement->load('points.user');
        return view('admin.settlements.show', compact('settlement'));
    }

    public function close(Request $request): RedirectResponse
    {
        $month = now()->startOfMonth();
        $paymentDue = $month->copy()->addMonth()->setDay(10);

        DB::transaction(function () use ($month, $paymentDue) {
            $settlement = PointSettlement::create([
                'settlement_month' => $month->endOfMonth()->toDateString(),
                'payment_due_date' => $paymentDue->toDateString(),
                'status'           => 'closed',
                'closed_by'        => Auth::guard('web')->id(),
                'closed_at'        => now(),
            ]);

            $points = Point::whereNull('settlement_id')
                ->where('type', 'earn')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->get();

            $total = $points->sum('amount');
            $points->each(fn($p) => $p->update(['settlement_id' => $settlement->id]));
            $settlement->update(['total_amount' => $total]);
        });

        return back()->with('success', '月末締め処理を実行しました。');
    }

    public function markPaid(PointSettlement $settlement): RedirectResponse
    {
        $settlement->update(['status' => 'paid']);
        return back()->with('success', '支払済みに更新しました。');
    }
}
