<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignDailySlot;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignDailySlotController extends Controller
{
    public function listAll(Request $request): View
    {
        $status = $request->input('status', 'published');
        $now    = now();
        $today    = $now->toDateString();
        $tomorrow = $now->copy()->addDay()->toDateString();
        $dayAfter = $now->copy()->addDays(2)->toDateString();

        $campaigns = Campaign::where('status', $status)
            ->orderBy('sort_order')->orderBy('id')->get();

        $campaignIds = $campaigns->pluck('id');

        $slots = CampaignDailySlot::whereIn('campaign_id', $campaignIds)
            ->whereBetween('target_date', [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ])
            ->get()
            ->groupBy('campaign_id');

        $statusCounts = Campaign::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('admin.daily_slots.list', compact(
            'campaigns', 'slots', 'status', 'statusCounts',
            'today', 'tomorrow', 'dayAfter'
        ));
    }

    public function importBulkTsv(Request $request): RedirectResponse
    {
        $request->validate(['tsv_file' => 'required|file|mimes:csv,txt|max:10240']);

        $content = file_get_contents($request->file('tsv_file')->getRealPath());
        $content = ltrim($content, "\xEF\xBB\xBF"); // BOM除去
        $lines   = array_values(array_filter(
            explode("\n", str_replace(["\r\n", "\r"], "\n", $content)),
            fn($l) => trim($l) !== ''
        ));

        if (empty($lines)) {
            return back()->withErrors(['tsv_file' => 'ファイルが空です']);
        }

        $headers  = str_getcsv($lines[0], "\t");
        $yearNow  = now()->year;
        $dateCols = [];
        for ($i = 1; $i < count($headers); $i++) {
            $col = trim($headers[$i]);
            if (preg_match('/^(\d{1,2})\/(\d{1,2})$/', $col, $m)) {
                $dateCols[$i] = sprintf('%04d-%02d-%02d', $yearNow, (int)$m[1], (int)$m[2]);
            }
        }

        $imported = 0;
        $skipped  = [];

        // 全案件を取得してメモリでマッチング（大文字小文字・全角スペース無視）
        $allCampaigns = Campaign::whereNotNull('title')->get(['id', 'title', 'product_name']);

        $normalize = fn(string $s): string =>
            mb_strtolower(preg_replace('/[\s\x{3000}　]+/u', '', trim($s)));

        $campaignMap = [];
        foreach ($allCampaigns as $c) {
            if ($c->product_name) {
                $campaignMap[$normalize($c->product_name)] = $c;
            }
            $campaignMap[$normalize($c->title)] = $c;
        }

        for ($r = 1; $r < count($lines); $r++) {
            $cols        = str_getcsv($lines[$r], "\t");
            $productName = trim($cols[0] ?? '');
            if ($productName === '') continue;

            $key      = $normalize($productName);
            $campaign = $campaignMap[$key] ?? null;

            if (!$campaign) {
                $skipped[] = $productName;
                continue;
            }

            foreach ($dateCols as $colIdx => $date) {
                $val = isset($cols[$colIdx]) ? trim($cols[$colIdx]) : '';
                if ($val === '') continue;
                CampaignDailySlot::updateOrCreate(
                    ['campaign_id' => $campaign->id, 'target_date' => $date],
                    ['planned_count' => max(0, (int) $val)]
                );
                $imported++;
            }
        }

        $msg = "{$imported}件インポートしました。";
        if ($skipped) {
            $msg .= "\nマッチしない商品名（案件タイトルまたは商品名と一致させてください）:\n"
                  . implode("\n", array_unique($skipped));
        }

        return back()->with('success', $msg);
    }

    public function index(Campaign $campaign): View
    {
        $slots = $campaign->dailySlots()->paginate(30);
        return view('admin.daily_slots.index', compact('campaign', 'slots'));
    }

    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        $request->validate([
            'target_date'   => 'required|date',
            'planned_count' => 'required|integer|min:0',
            'memo'          => 'nullable|string|max:500',
        ]);

        CampaignDailySlot::updateOrCreate(
            ['campaign_id' => $campaign->id, 'target_date' => $request->target_date],
            ['planned_count' => $request->planned_count, 'memo' => $request->memo]
        );

        return back()->with('success', '日別件数を保存しました。');
    }

    public function update(Request $request, Campaign $campaign, CampaignDailySlot $slot): RedirectResponse
    {
        $request->validate([
            'planned_count' => 'required|integer|min:0',
            'memo'          => 'nullable|string|max:500',
        ]);

        $slot->update($request->only(['planned_count', 'memo']));
        return back()->with('success', '更新しました。');
    }

    public function destroy(Campaign $campaign, CampaignDailySlot $slot): RedirectResponse
    {
        $slot->delete();
        return back()->with('success', '削除しました。');
    }

    public function importCsv(Request $request, Campaign $campaign): RedirectResponse
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt|max:1024']);

        $file = $request->file('csv_file');
        $lines = array_filter(explode("\n", file_get_contents($file->getRealPath())));

        $imported = 0;
        $errors = [];

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            if ($line === '') continue;

            $parts = str_getcsv($line);
            if (count($parts) < 2) {
                $errors[] = ($lineNum + 1) . "行目: 形式が不正です（日付,件数）";
                continue;
            }

            [$rawDate, $rawCount] = $parts;
            $rawDate  = trim($rawDate);
            $rawCount = trim($rawCount);

            // 月/日 形式 → 今年の日付に変換
            if (preg_match('/^(\d{1,2})\/(\d{1,2})$/', $rawDate, $m)) {
                $rawDate = Carbon::now()->year . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT);
            }

            try {
                $date  = Carbon::parse($rawDate)->toDateString();
                $count = max(0, (int) $rawCount);
            } catch (\Exception) {
                $errors[] = ($lineNum + 1) . "行目: 日付が解析できません（{$rawDate}）";
                continue;
            }

            CampaignDailySlot::updateOrCreate(
                ['campaign_id' => $campaign->id, 'target_date' => $date],
                ['planned_count' => $count]
            );
            $imported++;
        }

        $message = "{$imported}件インポートしました。";
        if ($errors) {
            $message .= ' エラー: ' . implode(' / ', $errors);
        }

        return back()->with('success', $message);
    }
}
