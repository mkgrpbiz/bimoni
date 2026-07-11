<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CancellationController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('liff')->user();

        $campaigns = Application::where('user_id', $user->id)
            ->whereIn('status', ['completed', 'reported', 'approved', 'point_granted'])
            ->with('campaign')
            ->orderByRaw('COALESCE(invited_at, applied_at) DESC')
            ->get()
            ->pluck('campaign')
            ->filter(fn ($campaign) => $campaign && $campaign->cancellation_visible && $campaign->hasCancellationInfo())
            ->unique('id')
            ->values();

        return view('member.cancellations.index', compact('campaigns'));
    }
}
