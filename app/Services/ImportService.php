<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\MonitorReport;
use App\Models\Point;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ImportService
{
    public function importUsers(array $rows, ?string $defaultReferralCode = null): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        DB::transaction(function () use ($rows, $defaultReferralCode, &$result) {
            foreach ($rows as $i => $row) {
                $line = $i + 2;

                $name = $row['name'] ?? $row['名前'] ?? '';
                if (empty($name)) {
                    $result['errors'][] = "{$line}行目: 氏名が空です";
                    continue;
                }

                // erme_respondent_id / 回答者ID 重複チェック
                $ermeId = $row['erme_respondent_id'] ?? $row['回答者ID'] ?? $row['respondent_id'] ?? null;
                if (!empty($ermeId)) {
                    if (User::where('erme_respondent_id', $ermeId)->exists()) {
                        $result['skipped']++;
                        continue;
                    }
                }

                // メールアドレス重複チェック
                $email = $row['email'] ?? $row['メールアドレス'] ?? null;
                if ($email && User::where('email', $email)->exists()) {
                    $result['skipped']++;
                    continue;
                }

                // 紹介コード（行内指定 > フォーム選択 > なし）
                $referralCode = $row['referred_by_code'] ?? $row['紹介コード'] ?? $defaultReferralCode ?? null;

                // 性別マッピング
                $genderRaw = $row['gender'] ?? $row['性別'] ?? '';
                $gender = match($genderRaw) {
                    '男性', 'male'   => 'male',
                    '女性', 'female' => 'female',
                    default          => in_array($genderRaw, ['male', 'female', 'other']) ? $genderRaw : null,
                };

                // 生年月日
                $birthdate = !empty($row['birthdate'] ?? $row['生年月日'] ?? '')
                    ? $this->parseDate($row['birthdate'] ?? $row['生年月日'] ?? '')
                    : null;

                $nameKana = $row['name_kana'] ?? $row['フリガナ'] ?? null;

                User::create([
                    'erme_respondent_id' => $ermeId ?: null,
                    'name'               => $name,
                    'name_kana'          => $nameKana,
                    'gender'             => $gender,
                    'birthdate'          => $birthdate,
                    'email'              => $email ?: null,
                    'referred_by_code'   => $referralCode,
                    'imported_from'      => 'spreadsheet',
                ]);

                $result['success']++;
            }
        });

        return $result;
    }

    public function importApplications(array $rows, ?string $campaignName = null): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        $rows = $this->normalizeApplicationRows($rows);

        $statusMap = [
            '実施完了'     => 'completed',
            '実施確認中'   => 'confirming',
            'キャンセル'   => 'cancelled',
            '予約中'       => 'scheduled',
            '打診中'       => 'line_contacted',
            '選考中'       => 'pending',
        ];
        $validStatuses = ['pending','line_contacted','scheduled','confirming','completed','reported','approved','point_granted','cancelled'];

        DB::transaction(function () use ($rows, $campaignName, &$result, $statusMap, $validStatuses) {
            foreach ($rows as $i => $row) {
                $line = $i + 2;

                $ermeId = $row['erme_respondent_id'] ?? '';
                if (empty($ermeId)) {
                    continue;
                }

                $targetCampaignName = $campaignName ?: ($row['campaign_name'] ?? '');
                if (empty($targetCampaignName)) {
                    $result['errors'][] = "{$line}行目: 案件名が指定されていません";
                    continue;
                }

                $campaign = Campaign::where('title', $targetCampaignName)->first();
                if (!$campaign) {
                    $result['errors'][] = "{$line}行目: 案件「{$targetCampaignName}」が見つかりません";
                    continue;
                }

                $user = User::where('erme_respondent_id', $ermeId)->first();
                if (!$user) {
                    $gender = match($row['gender'] ?? '') {
                        '女性' => 'female',
                        '男性' => 'male',
                        default => null,
                    };
                    $wantsCont = match($row['wants_continuation'] ?? '') {
                        'はい' => 1,
                        'いいえ' => 0,
                        default => null,
                    };
                    $times = null;
                    if (!empty($row['available_times']) && $row['available_times'] !== 'いつでもOK') {
                        $times = array_values(array_filter(array_map('trim', explode(',', $row['available_times']))));
                    }
                    $user = User::create([
                        'line_user_id'       => 'IMPORT_' . uniqid(),
                        'erme_respondent_id' => $ermeId,
                        'name'               => $row['name'] ?? ($row['name_kana'] ?? ''),
                        'name_kana'          => $row['name_kana'] ?? null,
                        'gender'             => $gender,
                        'birthdate'          => $this->parseDate($row['birthdate'] ?? ''),
                        'available_times'    => $times ?: null,
                        'wants_continuation' => $wantsCont,
                        'imported_from'      => 'spreadsheet',
                    ]);
                }

                if (Application::where('user_id', $user->id)->where('campaign_id', $campaign->id)->exists()) {
                    $result['skipped']++;
                    continue;
                }

                $rawStatus = $row['status'] ?? '';
                $status = $statusMap[$rawStatus] ?? (in_array($rawStatus, $validStatuses) ? $rawStatus : 'pending');

                Application::create([
                    'user_id'       => $user->id,
                    'campaign_id'   => $campaign->id,
                    'status'        => $status,
                    'applied_at'    => $this->parseDateTime($row['applied_at'] ?? '') ?? now(),
                    'selected_at'   => $this->parseDate($row['selected_at'] ?? ''),
                    'completed_at'  => $status === 'completed' ? ($this->parseDate($row['selected_at'] ?? '') ?? now()) : null,
                    'imported_from' => 'spreadsheet',
                ]);

                $result['success']++;
            }
        });

        return $result;
    }

    private function normalizeApplicationRows(array $rows): array
    {
        if (empty($rows)) return $rows;

        $headerMap = [
            '回答日時'                          => 'applied_at',
            '回答者ID'                          => 'erme_respondent_id',
            'お名前(漢字フルネーム)'            => 'name',
            'フリガナ'                          => 'name_kana',
            '生年月日をご入力ください'          => 'birthdate',
            '性別を選択してください'            => 'gender',
            '購入可能時間を選択して下さい'      => 'available_times',
            '継続購入がある場合、複数回の購入を希望されますか？' => 'wants_continuation',
            'ステータス'                        => 'status',
            '案内日'                            => 'selected_at',
            'ｷｬﾝﾍﾟｰﾝ'                          => 'campaign_name',
        ];

        $firstKeys = array_keys($rows[0]);
        $hasJapanese = collect($firstKeys)->contains(fn($k) => isset($headerMap[$k]));
        if (!$hasJapanese) return $rows;

        return array_map(function ($row) use ($headerMap) {
            $normalized = [];
            foreach ($row as $key => $value) {
                $normalized[$headerMap[$key] ?? $key] = $value;
            }
            return $normalized;
        }, $rows);
    }

    private function parseDateTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;
        if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})\s+(\d{1,2}):(\d{2})/', $value, $m)) {
            return sprintf('%04d-%02d-%02d %02d:%02d:00', $m[1], $m[2], $m[3], $m[4], $m[5]);
        }
        return $value;
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

    public function importReports(array $rows): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        DB::transaction(function () use ($rows, &$result) {
            foreach ($rows as $i => $row) {
                $line = $i + 2;

                // 案件名は必須
                $campaignName = $row['案件名'] ?? $row['campaign_name'] ?? '';
                if (empty($campaignName)) {
                    $result['errors'][] = "{$line}行目: 案件名が空です";
                    continue;
                }

                $campaign = Campaign::where('title', $campaignName)->first();
                if (!$campaign) {
                    $result['errors'][] = "{$line}行目: 案件「{$campaignName}」が見つかりません";
                    continue;
                }

                // ユーザー特定: 回答者IDで検索 → なければ名前で検索 → なければ新規作成
                $ermeId = $row['回答者ID'] ?? $row['erme_respondent_id'] ?? null;
                $name   = $row['名前'] ?? $row['name'] ?? $row['回答者名'] ?? null;
                $kana   = $row['フリガナ'] ?? $row['name_kana'] ?? null;
                $refCode = $row['紹介コード'] ?? $row['referred_by_code'] ?? null;

                $user = null;
                if ($ermeId) {
                    $user = User::where('erme_respondent_id', $ermeId)->first();
                }
                if (!$user && $name) {
                    $q = User::where('name', $name);
                    if ($kana) $q->where('name_kana', $kana);
                    $found = $q->get();
                    if ($found->count() === 1) {
                        $user = $found->first();
                    }
                }
                if (!$user) {
                    if (empty($name)) {
                        $result['errors'][] = "{$line}行目: ユーザーが特定できません（回答者IDまたは名前が必要）";
                        continue;
                    }
                    $user = User::create([
                        'line_user_id'       => 'IMPORT_' . uniqid(),
                        'erme_respondent_id' => $ermeId ?: null,
                        'name'               => $name,
                        'name_kana'          => $kana,
                        'referred_by_code'   => $refCode ?: null,
                        'imported_from'      => 'spreadsheet',
                    ]);
                } elseif ($refCode && !$user->referred_by_code) {
                    $user->update(['referred_by_code' => $refCode]);
                }

                // 応募レコードを確保（なければ作成）
                $application = Application::firstOrCreate(
                    ['user_id' => $user->id, 'campaign_id' => $campaign->id],
                    ['status' => 'completed', 'applied_at' => now(), 'completed_at' => now(), 'imported_from' => 'spreadsheet']
                );

                // 同一ユーザー×案件の報告が既にあればスキップ
                if (MonitorReport::where('user_id', $user->id)->where('campaign_id', $campaign->id)->exists()) {
                    $result['skipped']++;
                    continue;
                }

                // 初回か継続
                $purchaseRaw  = $row['初回か継続'] ?? $row['purchase_type'] ?? '';
                $purchaseType = match($purchaseRaw) {
                    '継続', 'continuation' => 'continuation',
                    default                => 'initial',
                };

                // ステータス
                $statusRaw = $row['ステータス'] ?? $row['status'] ?? '';
                $status    = match($statusRaw) {
                    '否認', 'rejected'              => 'rejected',
                    '保留', 'pending_review'         => 'pending_review',
                    default                          => 'approved',
                };

                MonitorReport::create([
                    'user_id'        => $user->id,
                    'campaign_id'    => $campaign->id,
                    'application_id' => $application->id,
                    'status'         => $status,
                    'purchase_type'  => $purchaseType,
                    'payment_status' => 'pending',
                ]);

                $result['success']++;
            }
        });

        return $result;
    }

    public function importCampaigns(array $rows): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        $rows = $this->normalizeCampaignRows($rows);

        $statusMap = [
            '実施中'   => 'published',
            '募集中'   => 'published',
            '一時停止' => 'paused',
            '終了'     => 'closed',
            '準備中'   => 'draft',
        ];
        $validStatuses = ['draft', 'published', 'paused', 'closed'];
        $validMedia    = ['AD', 'IF', 'LINE', 'monitor'];
        $validClosing  = ['20日', '25日', '月末'];
        $validPayment  = ['翌月末', '翌々月末'];

        DB::transaction(function () use ($rows, &$result, $statusMap, $validStatuses, $validMedia, $validClosing, $validPayment) {
            foreach ($rows as $i => $row) {
                $line = $i + 2;

                $title = $row['title'] ?? '';
                if (empty($title) || $title === '関数用') {
                    continue;
                }

                if (Campaign::where('title', $title)->exists()) {
                    $result['skipped']++;
                    continue;
                }

                $rawStatus = $row['status'] ?? '';
                $status = $statusMap[$rawStatus] ?? (in_array($rawStatus, $validStatuses) ? $rawStatus : 'draft');

                $media = $row['pr_media'] ?? '';
                if ($media === 'Instagram') $media = 'IF';
                $media = in_array($media, $validMedia) ? $media : null;

                $isVisible = strtoupper($row['_deny_all'] ?? 'FALSE') === 'TRUE' ? 0 : 1;

                Campaign::create([
                    'title'                => $title,
                    'campaign_type'        => 'product',
                    'status'               => $status,
                    'pr_media'             => $media,
                    'campaign_unit_price'  => $this->parseAmount($row['campaign_unit_price'] ?? ''),
                    'initial_purchase_fee' => $this->parseAmount($row['initial_purchase_fee'] ?? ''),
                    'recurring_purchase_fee' => $this->parseAmount($row['recurring_purchase_fee'] ?? ''),
                    'cooperation_fee'      => $this->parseAmount($row['cooperation_fee'] ?? '') ?? 0,
                    'referral_fee'         => $this->parseAmount($row['referral_fee'] ?? '') ?? 0,
                    'continuation_rate'    => $this->parsePercent($row['continuation_rate'] ?? ''),
                    'gross_profit'         => $this->parseAmount($row['gross_profit'] ?? ''),
                    'target_male_ratio'    => $this->parsePercent($row['target_male_ratio'] ?? ''),
                    'target_female_ratio'  => $this->parsePercent($row['target_female_ratio'] ?? ''),
                    'closing_date'         => in_array($row['closing_date'] ?? '', $validClosing) ? $row['closing_date'] : null,
                    'payment_timing'       => in_array($row['payment_timing'] ?? '', $validPayment) ? $row['payment_timing'] : null,
                    'requirements'         => $row['requirements'] ?? null,
                    'application_start_at' => $this->parseDate($row['application_start_at'] ?? ''),
                    'application_end_at'   => $this->parseDate($row['application_end_at'] ?? ''),
                    'is_visible'           => $isVisible,
                ]);

                $result['success']++;
            }
        });

        return $result;
    }

    private function normalizeCampaignRows(array $rows): array
    {
        if (empty($rows)) return $rows;

        $headerMap = [
            '案件名'           => 'title',
            'ステータス'       => 'status',
            'PR媒体'           => 'pr_media',
            '開始'             => 'application_start_at',
            '終了'             => 'application_end_at',
            '締め日'           => 'closing_date',
            '支払日'           => 'payment_timing',
            '報酬単価'         => 'campaign_unit_price',
            '初回'             => 'initial_purchase_fee',
            '継続'             => 'recurring_purchase_fee',
            '協力金'           => 'cooperation_fee',
            '紹介単価'         => 'referral_fee',
            '継続率'           => 'continuation_rate',
            '粗利'             => 'gross_profit',
            '男性比'           => 'target_male_ratio',
            '女性比'           => 'target_female_ratio',
            'モニター注意事項' => 'requirements',
            '全否認'           => '_deny_all',
            'シート名'         => '_sheet_name',
        ];

        $firstKeys = array_keys($rows[0]);
        $hasJapanese = collect($firstKeys)->contains(fn($k) => isset($headerMap[$k]));
        if (!$hasJapanese) return $rows;

        return array_map(function ($row) use ($headerMap) {
            $normalized = [];
            foreach ($row as $key => $value) {
                $normalized[$headerMap[$key] ?? $key] = $value;
            }
            return $normalized;
        }, $rows);
    }

    private function parseAmount(string $value): ?int
    {
        $value = trim($value);
        if ($value === '' || $value === '-') return null;
        $cleaned = str_replace(['¥', ',', ' '], '', $value);
        return is_numeric($cleaned) ? (int) $cleaned : null;
    }

    private function parsePercent(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') return null;
        $cleaned = str_replace('%', '', $value);
        return is_numeric($cleaned) ? (int) $cleaned : null;
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;
        if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $value, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        return $value;
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
