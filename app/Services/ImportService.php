<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Campaign;
use App\Models\Point;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ImportService
{
    public function importUsers(array $rows): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        DB::transaction(function () use ($rows, &$result) {
            foreach ($rows as $i => $row) {
                $line = $i + 2;

                if (empty($row['name'])) {
                    $result['errors'][] = "{$line}行目: 氏名が空です";
                    continue;
                }

                // erme_respondent_id 重複チェック
                if (!empty($row['erme_respondent_id'])) {
                    if (User::where('erme_respondent_id', $row['erme_respondent_id'])->exists()) {
                        $result['skipped']++;
                        continue;
                    }
                }

                $availableTimes = null;
                if (!empty($row['available_times'])) {
                    $availableTimes = array_filter(explode(';', $row['available_times']));
                }

                User::create([
                    'line_user_id'       => 'IMPORT_' . uniqid(),
                    'erme_respondent_id' => $row['erme_respondent_id'] ?? null,
                    'name'               => $row['name'],
                    'name_kana'          => $row['name_kana'] ?? null,
                    'gender'             => in_array($row['gender'] ?? '', ['male', 'female', 'other']) ? $row['gender'] : null,
                    'birthdate'          => !empty($row['birthdate']) ? $row['birthdate'] : null,
                    'area'               => $row['area'] ?? null,
                    'available_times'    => $availableTimes ?: null,
                    'wants_continuation' => isset($row['wants_continuation']) ? (int) $row['wants_continuation'] : null,
                    'point_balance'      => (int) ($row['point_balance'] ?? 0),
                    'imported_from'      => 'spreadsheet',
                ]);

                $result['success']++;
            }
        });

        return $result;
    }

    public function importApplications(array $rows): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        DB::transaction(function () use ($rows, &$result) {
            foreach ($rows as $i => $row) {
                $line = $i + 2;

                if (empty($row['erme_respondent_id']) || empty($row['campaign_name'])) {
                    $result['errors'][] = "{$line}行目: 必須項目が不足しています";
                    continue;
                }

                $user = User::where('erme_respondent_id', $row['erme_respondent_id'])->first();
                if (!$user) {
                    $result['errors'][] = "{$line}行目: エルメID「{$row['erme_respondent_id']}」のユーザーが見つかりません";
                    continue;
                }

                $campaign = Campaign::where('title', $row['campaign_name'])->first();
                if (!$campaign) {
                    $result['errors'][] = "{$line}行目: 案件「{$row['campaign_name']}」が見つかりません";
                    continue;
                }

                if (Application::where('user_id', $user->id)->where('campaign_id', $campaign->id)->exists()) {
                    $result['skipped']++;
                    continue;
                }

                $validStatuses = ['pending','line_contacted','scheduled','confirming','completed','reported','approved','point_granted','cancelled'];
                $status = in_array($row['status'] ?? '', $validStatuses) ? $row['status'] : 'pending';

                Application::create([
                    'user_id'       => $user->id,
                    'campaign_id'   => $campaign->id,
                    'status'        => $status,
                    'applied_at'    => $row['applied_at'] ?? now(),
                    'selected_at'   => $row['selected_at'] ?? null,
                    'completed_at'  => $row['completed_at'] ?? null,
                    'approved_at'   => $row['approved_at'] ?? null,
                    'imported_from' => 'spreadsheet',
                ]);

                $result['success']++;
            }
        });

        return $result;
    }

    public function importPoints(array $rows): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        DB::transaction(function () use ($rows, &$result) {
            foreach ($rows as $i => $row) {
                $line = $i + 2;

                if (empty($row['erme_respondent_id']) || empty($row['amount']) || empty($row['granted_at'])) {
                    $result['errors'][] = "{$line}行目: 必須項目が不足しています";
                    continue;
                }

                $user = User::where('erme_respondent_id', $row['erme_respondent_id'])->first();
                if (!$user) {
                    $result['errors'][] = "{$line}行目: エルメID「{$row['erme_respondent_id']}」のユーザーが見つかりません";
                    continue;
                }

                Point::create([
                    'user_id'       => $user->id,
                    'type'          => in_array($row['type'] ?? '', ['earn', 'exchange', 'adjust', 'cancel']) ? $row['type'] : 'earn',
                    'amount'        => (int) $row['amount'],
                    'reason'        => $row['reason'] ?? null,
                    'imported_from' => 'spreadsheet',
                    'created_at'    => $row['granted_at'],
                ]);

                $result['success']++;
            }
        });

        return $result;
    }

    public function parseCsv(string $content): array
    {
        $lines = array_filter(explode("\n", str_replace("\r\n", "\n", trim($content))));
        $lines = array_values($lines);

        if (count($lines) < 2) return [];

        $headers = str_getcsv(array_shift($lines));
        $headers = array_map('trim', $headers);

        $rows = [];
        foreach ($lines as $line) {
            $values = str_getcsv($line);
            if (count($values) === count($headers)) {
                $rows[] = array_combine($headers, array_map('trim', $values));
            }
        }

        return $rows;
    }
}
