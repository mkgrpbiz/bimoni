<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardSummaryService;
use Illuminate\Http\JsonResponse;

/**
 * Read-only bridge for AI OFFICE's BIMONI department dashboard. Every
 * figure here comes from DashboardSummaryService — the exact same
 * calculations the admin dashboard (Admin\DashboardController) uses — so
 * AI OFFICE never recomputes BIMONI's business numbers on its own.
 */
class AiOfficeDashboardController extends Controller
{
    public function summary(DashboardSummaryService $summary): JsonResponse
    {
        $now = now();

        return response()->json([
            'daily_kpi' => $summary->dailyKpi(),
            'pending_approvals' => $summary->pendingApprovals(),
            'monthly_metrics' => $summary->monthlyMetrics($now->year, $now->month, 'monthly'),
            'campaign_counts' => $summary->campaignCounts(),
            'alerts' => $summary->alerts(),
            'generated_at' => $now->toIso8601String(),
        ]);
    }
}
