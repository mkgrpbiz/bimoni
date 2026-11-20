<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignDailySlot;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // 表示対象月（デフォルトは今月）。過去に取り込んだ来月分等も確認できるよう切り替え可能にする
        $year  = (int) $request->input('year', $now->year);
        $month = (int) $request->input('month', $now->month);
        $viewMonth = Carbon::createFromDate($year, $month, 1);
        $prevMonth = $viewMonth->copy()->subMonth();
        $nextMonth = $viewMonth->copy()->addMonth();

        $campaigns = Campaign::where('status', $status)
            ->orderBy('sort_order')->orderBy('id')->get();

        $campaignIds = $campaigns->pluck('id');

        $slots = CampaignDailySlot::whereIn('campaign_id', $campaignIds)
            ->whereBetween('target_date', [
                $viewMonth->copy()->startOfMonth()->toDateString(),
                $viewMonth->copy()->endOfMonth()->toDateString(),
            ])
            ->get()
            ->groupBy('campaign_id')
            ->map(fn($cs) => $cs->keyBy(fn($s) => $s->target_date->toDateString()));

        $statusCounts = Campaign::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('admin.daily_slots.list', compact(
            'campaigns', 'slots', 'status', 'statusCounts',
            'today', 'tomorrow', 'dayAfter',
            'viewMonth', 'prevMonth', 'nextMonth'
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

        // 区切り文字を自動判定（タブ優先、なければカンマ）
        $delimiter = str_contains($lines[0], "\t") ? "\t" : ",";

        // 「商品名」または「案件名」を含む行をヘッダー行として探す
        $headerRowIdx = null;
        foreach ($lines as $idx => $line) {
            if (str_contains($line, '商品名') || str_contains($line, '案件名')) {
                $headerRowIdx = $idx;
                break;
            }
        }
        if ($headerRowIdx === null) {
            return back()->withErrors(['tsv_file' => '「商品名」または「案件名」を含むヘッダー行が見つかりません']);
        }

        $headers = str_getcsv($lines[$headerRowIdx], $delimiter);
        $headers = array_map('trim', $headers);

        // 「商品名」または「案件名」列のインデックスを探す
        $nameColIdx = null;
        foreach ($headers as $i => $h) {
            if (str_contains($h, '商品名') || str_contains($h, '案件名')) {
                $nameColIdx = $i;
                break;
            }
        }

        // 日付列を動的に検出
        $yearNow  = now()->year;
        $dateCols = [];
        foreach ($headers as $i => $col) {
            if (preg_match('/^(\d{1,2})\/(\d{1,2})$/', $col, $m)) {
                $dateCols[$i] = sprintf('%04d-%02d-%02d', $yearNow, (int)$m[1], (int)$m[2]);
            }
        }

        $imported = 0;
        $skipped  = [];

        // 全案件をメモリに読み込んでマッチング（スペース・大文字小文字無視）
        $allCampaigns = Campaign::whereNotNull('title')->get(['id', 'title', 'product_name']);

        $normalize = fn(string $s): string =>
            mb_strtolower(preg_replace('/[\s\x{3000}　]+/u', '', trim($s)));

        // 完全一致マップ
        $exactMap = [];
        foreach ($allCampaigns as $c) {
            if ($c->product_name) {
                $exactMap[$normalize($c->product_name)] = $c;
            }
            $exactMap[$normalize($c->title)] = $c;
        }

        for ($r = $headerRowIdx + 1; $r < count($lines); $r++) {
            $cols        = str_getcsv($lines[$r], $delimiter);
            $productName = trim($cols[$nameColIdx] ?? '');
            if ($productName === '') continue;

            $key = $normalize($productName);

            // 完全一致
            $campaign = $exactMap[$key] ?? null;

            // 部分一致フォールバック（どちらか一方がもう一方に含まれる）
            if (!$campaign) {
                foreach ($exactMap as $dbKey => $c) {
                    if (mb_strlen($dbKey) >= 3 && (str_contains($key, $dbKey) || str_contains($dbKey, $key))) {
                        $campaign = $c;
                        break;
                    }
                }
            }

            // 類似度マッチ（similar_text >= 75%）
            $bestMatch   = null;
            $bestPercent = 0;
            if (!$campaign) {
                foreach ($exactMap as $dbKey => $c) {
                    similar_text($key, $dbKey, $pct);
                    if ($pct > $bestPercent) {
                        $bestPercent = $pct;
                        $bestMatch   = $c;
                    }
                }
                if ($bestPercent >= 75) {
                    $campaign = $bestMatch;
                }
            }

            if (!$campaign) {
                $info = $productName;
                if (isset($bestMatch)) {
                    $info .= sprintf(' → 近候補: 「%s」%.0f%%', $bestMatch->title, $bestPercent);
                }
                $skipped[] = $info;
                continue;
            }

            foreach ($dateCols as $colIdx => $date) {
                $val = isset($cols[$colIdx]) ? trim($cols[$colIdx]) : '';
                if ($val === '' || !is_numeric($val)) continue;
                CampaignDailySlot::updateOrCreate(
                    ['campaign_id' => $campaign->id, 'target_date' => $date],
                    ['planned_count' => max(0, (int) $val)]
                );
                $imported++;
            }
        }

        $msg = "{$imported}件インポートしました。";
        if ($skipped) {
            $msg .= "\n\n【スキップ】案件タイトルと一致しない商品名:\n"
                  . implode("\n", array_unique($skipped));
        }

        return back()->with('success', $msg);
    }

    // 打診済/予約済/実施完了はCampaignDailySlotの実カラムだが、どこからも書き込まれないため
    // 常に0のまま（ApplicationController::campaignIndex()の「応募管理」ヘッダー集計が本日/明日/
    // 翌々日の3日分だけ同じロジックで一時的に計算しているが、この画面には反映されていなかった）。
    // この画面（全日付）でも応募の案内日時（invited_at、無ければPR打診用にinvited_end_at）を
    // 元にステータス別件数をその場で動的集計して表示する。
    // 打診済＝その日に案内した総数（現在のステータスに関わらず。cancelledになっても打診した事実は
    // 残すためinvited_at/invited_end_atでの絞り込みのみで、ステータスでの絞り込みはしない。
    // changeStatus('cancelled', ...)・cancelProposal()はinvited_at/invited_end_atをクリアしないため、
    // 手動キャンセル・自動キャンセルいずれでも打診済の母数から消えない）。予約済・実施完了は
    // その母数のうち該当ステータス以上に進んだ件数（＝予約済は実施完了も含む累積のファネル件数）として
    // 打診済→予約済→実施完了の推移が追えるようにする
    public function index(Campaign $campaign): View
    {
        $slots = $campaign->dailySlots()->paginate(30);

        $targetDates = $slots->getCollection()->map(fn($s) => $s->target_date->toDateString())->all();

        $appCounts = $campaign->applications()
            ->where(function ($q) use ($targetDates) {
                $q->whereIn(DB::raw('DATE(invited_at)'), $targetDates)
                  ->orWhereIn(DB::raw('DATE(invited_end_at)'), $targetDates);
            })
            ->selectRaw('DATE(COALESCE(invited_at, invited_end_at)) as inv_date, status, COUNT(*) as cnt')
            ->groupBy(DB::raw('DATE(COALESCE(invited_at, invited_end_at))'), 'status')
            ->get()
            ->groupBy('inv_date');

        $slots->getCollection()->each(function ($slot) use ($appCounts) {
            $dayCounts = $appCounts->get($slot->target_date->toDateString(), collect());
            $slot->invited_count   = $dayCounts->sum('cnt');
            $slot->reserved_count  = $dayCounts->filter(fn($r) => in_array($r->status, ['scheduled', 'confirming', 'completed', 'reported', 'approved', 'point_granted']))->sum('cnt');
            $slot->completed_count = $dayCounts->filter(fn($r) => in_array($r->status, ['confirming', 'completed', 'reported', 'approved', 'point_granted']))->sum('cnt');
            $slot->completion_rate = $slot->invited_count > 0 ? round($slot->completed_count / $slot->invited_count * 100, 1) : null;
        });

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
