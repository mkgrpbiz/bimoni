<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentReferralCode;
use App\Models\Campaign;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function __construct(private ImportService $importer) {}

    public function index(): View
    {
        $campaigns = Campaign::whereIn('status', ['published', 'paused'])
            ->orderBy('title')
            ->get();

        // 紹介コード一覧（代理店名付き）
        $referralCodes = AgentReferralCode::with('agent.parent')->get()->map(function ($arc) {
            $agentName = $arc->agent?->parent
                ? $arc->agent->parent->name . ' > ' . $arc->agent->name
                : ($arc->agent?->name ?? '');
            return ['code' => $arc->code, 'label' => $agentName . '（' . $arc->code . '）'];
        })->sortBy('label');

        return view('admin.import.index', compact('campaigns', 'referralCodes'));
    }

    public function importUsers(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file'      => 'required|file|mimes:csv,txt|max:5120',
            'referral_code' => 'nullable|string|exists:agent_referral_codes,code',
        ]);

        $content = file_get_contents($request->file('csv_file')->getRealPath());
        $rows    = $this->importer->parseCsv($content);

        if (empty($rows)) {
            return back()->with('error', 'CSVの読み込みに失敗しました。フォーマットを確認してください。');
        }

        $result = $this->importer->importUsers($rows, $request->referral_code);

        return back()->with('import_result', $result)->with('import_type', 'ユーザー');
    }

    public function importApplications(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file'      => 'required|file|mimes:csv,txt|max:5120',
            'campaign_name' => 'nullable|string|max:255',
        ]);

        $content = file_get_contents($request->file('csv_file')->getRealPath());
        $rows    = $this->importer->parseCsv($content);

        if (empty($rows)) {
            return back()->with('error', 'CSVの読み込みに失敗しました。フォーマットを確認してください。');
        }

        $result = $this->importer->importApplications($rows, $request->input('campaign_name'));

        return back()->with('import_result', $result)->with('import_type', '応募履歴');
    }

    public function importPoints(Request $request): RedirectResponse
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt|max:5120']);

        $content = file_get_contents($request->file('csv_file')->getRealPath());
        $rows    = $this->importer->parseCsv($content);

        if (empty($rows)) {
            return back()->with('error', 'CSVの読み込みに失敗しました。フォーマットを確認してください。');
        }

        $result = $this->importer->importPoints($rows);

        return back()->with('import_result', $result)->with('import_type', 'ポイント履歴');
    }

    public function importReports(Request $request): RedirectResponse
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt|max:5120']);

        $content = file_get_contents($request->file('csv_file')->getRealPath());
        $rows    = $this->importer->parseCsv($content);

        if (empty($rows)) {
            return back()->with('error', 'CSVの読み込みに失敗しました。フォーマットを確認してください。');
        }

        $result = $this->importer->importReports($rows);

        return back()->with('import_result', $result)->with('import_type', '報告');
    }

    public function importCampaigns(Request $request): RedirectResponse
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt|max:5120']);

        $content = file_get_contents($request->file('csv_file')->getRealPath());
        $rows    = $this->importer->parseCsv($content);

        if (empty($rows)) {
            return back()->with('error', 'CSVの読み込みに失敗しました。フォーマットを確認してください。');
        }

        $result = $this->importer->importCampaigns($rows);

        return back()->with('import_result', $result)->with('import_type', '案件');
    }
}
