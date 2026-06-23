<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonitorReport;
use App\Models\Point;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PointController extends Controller
{
    public function __construct(private PointService $pointService) {}

    public function index(Request $request): View
    {
        $query = Point::with(['user', 'application.campaign'])->latest('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $points = $query->paginate(30)->withQueryString();
        $users  = User::whereNotNull('name')->orderBy('name')->get();

        return view('admin.points.index', compact('points', 'users'));
    }

    public function grantForReport(MonitorReport $report): RedirectResponse
    {
        if ($report->status !== 'approved') {
            return back()->with('error', '承認済みの報告のみ付与できます。');
        }

        $this->pointService->grantForReport($report);

        return back()->with('success', '協力金を付与しました。');
    }

    public function adjust(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount'  => 'required|integer|not_in:0',
            'reason'  => 'required|string|max:255',
        ]);

        $this->pointService->adjust($request->user_id, $request->amount, $request->reason);

        return back()->with('success', 'ポイントを調整しました。');
    }
}
