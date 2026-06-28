<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
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

        // 代理店一覧（親 > 子の構造で）
        $parentAgents = Agent::whereNull('parent_id')->with('children.codes')->orderBy('name')->get();

        return view('admin.import.index', compact('campaigns', 'parentAgents'));
    }

    public function importUsers(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
            'agent_id' => 'nullable|integer|exists:agents,id',
        ]);

        $content = file_get_contents($request->file('csv_file')->getRealPath());
        $rows    = $this->importer->parseCsv($content);

        if (empty($rows)) {
            return back()->with('error', 'CSVの読み込みに失敗しました。フォーマットを確認してください。');
        }

        $result = $this->importer->importUsers($rows, $request->filled('agent_id') ? (int) $request->agent_id : null);

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
