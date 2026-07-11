<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonitorReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::withCount(['applications'])
            ->whereNotNull('profile_completed_at')
            ->orderByDesc('created_at');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qb) use ($q) {
                $qb->where('bimoni_user_id', 'like', "%{$q}%")
                   ->orWhere('line_display_name', 'like', "%{$q}%")
                   ->orWhere('name', 'like', "%{$q}%")
                   ->orWhere('name_kana', 'like', "%{$q}%");
            });
        }

        $users = $query->paginate(30)->withQueryString();

        $userIds = $users->pluck('id');

        $reportRows = MonitorReport::with('campaign:id,cooperation_fee,continuation_cooperation_fee')
            ->whereIn('user_id', $userIds)
            ->where('status', 'approved')
            ->get();

        $completedMap = $reportRows->groupBy('user_id')
            ->map(fn($r) => $r->count());

        $calcFee = function ($row) {
            $coopFee = $row->purchase_type === 'continuation'
                ? ($row->campaign?->continuation_cooperation_fee ?? 0)
                : ($row->campaign?->cooperation_fee ?? 0);
            return ($row->purchase_amount ?? 0) + $coopFee + ($row->bonus_amount ?? 0) + ($row->adjustment_amount ?? 0);
        };

        $pendingMap = $reportRows->where('payment_status', 'pending')
            ->groupBy('user_id')
            ->map(fn($r) => $r->sum($calcFee));

        $paidMap = $reportRows->where('payment_status', 'paid')
            ->groupBy('user_id')
            ->map(fn($r) => $r->sum($calcFee));

        return view('admin.users.index', compact('users', 'completedMap', 'pendingMap', 'paidMap'));
    }

    public function show(User $user): View
    {
        $reports = $user->monitorReports()
            ->with('campaign:id,title,cooperation_fee,continuation_cooperation_fee')
            ->orderByDesc('created_at')
            ->get();

        $applications = $user->applications()
            ->with('campaign:id,title,status')
            ->orderByDesc('applied_at')
            ->get();

        $collectionReports = $user->collectionReports()
            ->orderByDesc('created_at')
            ->get();

        return view('admin.users.show', compact('user', 'reports', 'applications', 'collectionReports'));
    }
}
