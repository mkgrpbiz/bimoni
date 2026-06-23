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
