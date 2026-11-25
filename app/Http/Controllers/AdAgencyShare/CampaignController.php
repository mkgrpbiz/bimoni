<?php

namespace App\Http\Controllers\AdAgencyShare;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'published');
        if (!in_array($status, ['published', 'paused', 'closed'], true)) {
            $status = 'published';
        }

        $query = Campaign::withCount([
                'applications as applications_total_count',
                'applications as applications_pending_count' => fn ($q) => $q->where('status', 'pending'),
                'applications as applications_completed_count' => fn ($q) => $q->whereIn('status', ['completed', 'reported', 'approved', 'point_granted']),
            ])
            ->orderBy('sort_order')->orderBy('id')
            ->where('status', $status);

        if ($request->filled('campaign_type')) {
            $query->where('campaign_type', $request->campaign_type);
        }
        if ($request->filled('pr_media')) {
            $query->where('pr_media', $request->pr_media);
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        $campaigns = $query->paginate(50)->withQueryString();

        $statusCounts = Campaign::whereIn('status', ['published', 'paused', 'closed'])
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('ad_agency_share.campaigns.index', compact('campaigns', 'status', 'statusCounts'));
    }
}
