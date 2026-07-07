<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentReferralCode;
use App\Models\Application;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\CollectionReport;
use App\Models\MonitorReport;
use App\Models\Point;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ImportService
{
    public function importUsers(array $rows, ?int $agentId = null): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        // 代理店のデフォルトコード（コードが1つだけの場合に使用）
        $defaultReferralCode = null;
        if ($agentId) {
            $agent = Agent::with('codes')->find($agentId);
            if ($agent && $agent->codes->count() === 1) {
                $defaultReferralCode = $agent->codes->first()->code;
            }
        }

        // 既存コードをキャッシュ（ループ内での都度クエリを避ける）
        $existingCodes = AgentReferralCode::pluck('code')->flip()->toArray();

        DB::transaction(function () use ($rows, $agentId, $defaultReferralCode, &$existingCodes, &$result) {
            foreach ($rows as $i => $row) {
                $line = $i + 2;

                $name = preg_replace('/[\s\x{3000}]+/u', '', $row['name'] ?? $row['名前'] ?? '');
                if (empty($name)) {
                    $result['errors'][] = "{$line}行目: 氏名が空です";
                    continue;
                }

                // erme_respondent_id / 回答者ID 重複チェック（一致すれば上書き更新）
                $ermeId = $row['erme_respondent_id'] ?? $row['回答者ID'] ?? $row['respondent_id'] ?? null;
                $existingByErme = !empty($ermeId) ? User::where('erme_respondent_id', $ermeId)->first() : null;

                // メールアドレス重複チェック
                $email = $row['email'] ?? $row['メールアドレス'] ?? null;

                // 紹介コード（行内指定 > デフォルト > なし）
                $csvCode      = $row['referred_by_code'] ?? $row['紹介コード'] ?? null;
                $referralCode = $csvCode ?: $defaultReferralCode;

                // CSVのコードが未登録かつ代理店が選択されている場合は自動発行
                if ($csvCode && $agentId && !isset($existingCodes[$csvCode])) {
                    AgentReferralCode::create(['agent_id' => $agentId, 'code' => $csvCode]);
                    $existingCodes[$csvCode] = true;
                }

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
                if ($nameKana !== null) {
                    $nameKana = preg_replace('/[\s\x{3000}]+/u', '', $nameKana) ?: null;
                }

                $lineDisplayName = $row['line_display_name'] ?? $row['回答者名（任意）'] ?? null;

                if ($existingByErme) {
                    // erme_respondent_id 一致 → 上書き更新
                    $existingByErme->update([
                        'line_display_name' => $lineDisplayName ?: $existingByErme->line_display_name,
                        'name'              => $name,
                        'name_kana'         => $nameKana ?? $existingByErme->name_kana,
                        'gender'            => $gender ?? $existingByErme->gender,
                        'birthdate'         => $birthdate ?? $existingByErme->birthdate,
                        'email'             => $email ?: $existingByErme->email,
                        'referred_by_code'  => $referralCode ?: $existingByErme->referred_by_code,
                    ]);
                    $result['success']++;
                } else {
                    // メール重複チェック（erme不一致の場合のみ）
                    if ($email && User::where('email', $email)->exists()) {
                        $result['skipped']++;
                        continue;
                    }

                    User::create([
                        'line_user_id'         => 'IMPORT_' . uniqid(),
                        'erme_respondent_id'   => $ermeId ?: null,
                        'line_display_name'    => $lineDisplayName ?: null,
                        'name'                 => $name,
                        'name_kana'            => $nameKana,
                        'gender'               => $gender,
                        'birthdate'            => $birthdate,
                        'email'                => $email ?: null,
                        'referred_by_code'     => $referralCode,
                        'profile_completed_at' => now(),
                        'imported_from'        => 'spreadsheet',
                    ]);
                    $result['success']++;
                }
            }
        });

        return $result;
    }

    public function importApplications(array $rows, int $campaignId): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        $campaign = Campaign::find($campaignId);
        if (!$campaign) {
            $result['errors'][] = '指定された案件が見つかりません';
            return $result;
        }

        $rows = $this->normalizeApplicationRows($rows);

        $statusMap = [
            '実施完了'   => 'completed',
            '実施確認中' => 'confirming',
            'キャンセル' => 'cancelled',
            '予約中'     => 'scheduled',
            '打診中'     => 'line_contacted',
            '選考中'     => 'pending',
            '応募'       => 'pending',
        ];
        $validStatuses = ['pending','line_contacted','scheduled','confirming','completed','reported','approved','point_granted','cancelled'];

        DB::transaction(function () use ($rows, $campaign, &$result, $statusMap, $validStatuses) {
            foreach ($rows as $i => $row) {
                $ermeId = trim($row['erme_respondent_id'] ?? '');
                if (empty($ermeId)) {
                    continue;
                }

                $appliedAtStr = $this->parseDateTime($row['applied_at'] ?? '') ?? now()->toDateTimeString();
                $appliedAtCarbon = Carbon::parse($appliedAtStr);

                // ユーザー検索（なければ作成）
                $times = null;
                if (!empty($row['available_times'])) {
                    $times = array_values(array_filter(array_map('trim', explode(',', $row['available_times']))));
                }

                $user = User::where('erme_respondent_id', $ermeId)->first();
                if (!$user) {
                    $gender = match($row['gender'] ?? '') {
                        '女性' => 'female',
                        '男性' => 'male',
                        default => null,
                    };
                    $user = User::create([
                        'line_user_id'         => 'IMPORT_' . uniqid(),
                        'erme_respondent_id'   => $ermeId,
                        'line_display_name'    => $row['line_display_name'] ?? null,
                        'name'                 => $row['name'] ?? ($row['name_kana'] ?? ''),
                        'name_kana'            => $row['name_kana'] ?? null,
                        'gender'               => $gender,
                        'birthdate'            => $this->parseDate($row['birthdate'] ?? ''),
                        'available_times'      => $times ?: null,
                        'imported_from'        => 'spreadsheet',
                        'profile_completed_at' => $appliedAtCarbon,
                    ]);
                } elseif ($times) {
                    $user->update(['available_times' => $times]);
                }

                $rawStatus = trim($row['status'] ?? '');
                $status = $rawStatus !== ''
                    ? ($statusMap[$rawStatus] ?? (in_array($rawStatus, $validStatuses) ? $rawStatus : 'pending'))
                    : 'pending';

                $invitedDate = trim($row['invited_date'] ?? '');
                $invitedTime = trim($row['invited_time'] ?? '');
                $invitedAt = null;
                if ($invitedDate !== '') {
                    $str = $invitedDate . ($invitedTime !== '' ? ' ' . $invitedTime : '');
                    $invitedAt = $this->parseDateTime($str) ?? $this->parseDate($invitedDate);
                }

                // 継続購入希望（はい→希望 / いいえ→不可）
                $continuationWish = match($row['wants_continuation'] ?? '') {
                    'はい'   => '希望',
                    'いいえ' => '不可',
                    default  => null,
                };

                // 継続打診承諾（TRUE/FALSE → possible/not_possible）
                $flagRaw = strtoupper(trim($row['continuation_flag'] ?? ''));
                $continuationResponse = match($flagRaw) {
                    'TRUE'  => 'possible',
                    'FALSE' => 'not_possible',
                    default => null,
                };

                $completedAt = null;
                if (in_array($status, ['completed', 'reported', 'approved', 'point_granted'])) {
                    $completedAt = $invitedAt ?? $appliedAtStr;
                }

                $data = [
                    'status'                => $status,
                    'invited_at'            => $invitedAt,
                    'completed_at'          => $completedAt,
                    'continuation_wish'     => $continuationWish,
                    'continuation_response' => $continuationResponse,
                    'imported_from'         => 'spreadsheet',
                ];

                // 同一ユーザー×同一応募日時 → 上書き更新、なければ新規作成
                $existing = Application::where('user_id', $user->id)
                    ->where('applied_at', $appliedAtStr)
                    ->first();

                if ($existing) {
                    $existing->update($data);
                    $application = $existing;
                } else {
                    $application = Application::create(array_merge($data, [
                        'user_id'     => $user->id,
                        'campaign_id' => $campaign->id,
                        'applied_at'  => $appliedAtStr,
                    ]));
                    DB::table('applications')->where('id', $application->id)->update(['created_at' => $appliedAtCarbon]);
                }

                $result['success']++;
            }
        });

        return $result;
    }

    private function normalizeApplicationRows(array $rows): array
    {
        if (empty($rows)) return $rows;

        $headerMap = [
            '回答日時'         => 'applied_at',
            '回答者ID'         => 'erme_respondent_id',
            '回答者'           => 'line_display_name',
            'お名前(漢字フルネーム)' => 'name',
            'フリガナ'         => 'name_kana',
            '生年月日をご入力ください' => 'birthdate',
            '性別を選択してください'  => 'gender',
            '購入可能時間を選択して下さい' => 'available_times',
            '継続購入がある場合、複数回の購入を希望されますか？' => 'wants_continuation',
            'ステータス'       => 'status',
            '案内日'           => 'invited_date',
            '案内時間'         => 'invited_time',
            '継続'             => 'continuation_flag',
            '奨学'             => 'continuation_flag',
            '備考'             => 'notes',
            'ｷｬﾝﾍﾟｰﾝ'         => 'campaign_name',
            'キャンペーン'     => 'campaign_name',
        ];

        $firstKeys = array_keys($rows[0]);
        $hasJapanese = collect($firstKeys)->contains(fn($k) => isset($headerMap[$k]));
        if (!$hasJapanese) return $rows;

        return array_map(function ($row) use ($headerMap) {
            $normalized = [];
            foreach ($row as $key => $value) {
                if ($key === 'ステータス共有') continue;
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

                $ermeId          = trim($row['回答者ID'] ?? '');
                $lineDisplayName = $row['回答者名(任意)'] ?? $row['回答者名（任意）'] ?? $row['回答者名'] ?? null;
                $name            = preg_replace('/[\s\x{3000}]+/u', '', $row['名前'] ?? '') ?: null;
                $kana            = preg_replace('/[\s\x{3000}]+/u', '', $row['フリガナ'] ?? '') ?: null;

                // ユーザー特定: 回答者IDで検索 → なければ名前+フリガナで検索 → なければ新規作成
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
                        $result['errors'][] = "{$line}行目: 名前が空のためユーザーを作成できません";
                        continue;
                    }
                    $user = User::create([
                        'line_user_id'         => 'IMPORT_' . uniqid(),
                        'erme_respondent_id'   => $ermeId ?: null,
                        'line_display_name'    => $lineDisplayName ?: null,
                        'name'                 => $name,
                        'name_kana'            => $kana,
                        'profile_completed_at' => now(),
                        'imported_from'        => 'spreadsheet',
                    ]);
                }

                // 報告日時（空欄は現在時刻）
                $reportedAtRaw = trim($row['報告日時'] ?? '');
                $reportedAt    = $reportedAtRaw !== '' ? Carbon::parse($reportedAtRaw) : now();

                // 重複チェック: ユーザー×案件×報告日時（完全一致）
                if (MonitorReport::where('user_id', $user->id)
                    ->where('campaign_id', $campaign->id)
                    ->where('created_at', $reportedAt->toDateTimeString())
                    ->exists()) {
                    $result['skipped']++;
                    continue;
                }

                // 初回か継続（完全一致）
                $purchaseRaw  = $row['初回か継続'] ?? '';
                $purchaseType = match($purchaseRaw) {
                    '継続'  => 'continuation',
                    default => 'initial',
                };

                $purchaseAmount = (int) preg_replace('/[^\d]/', '', $row['モニター経費'] ?? $row['商品金額'] ?? '0');

                // キャンペーン列 → bonus_amount
                $bonusRaw    = trim($row['キャンペーン'] ?? '');
                $bonusAmount = $bonusRaw !== '' ? (int) preg_replace('/[^\d]/', '', $bonusRaw) : null;

                // 既存の応募レコードを探すだけ（作成しない）
                $application = Application::where('user_id', $user->id)
                    ->where('campaign_id', $campaign->id)
                    ->first();

                $report = MonitorReport::create([
                    'user_id'         => $user->id,
                    'campaign_id'     => $campaign->id,
                    'application_id'  => $application?->id,
                    'status'          => 'approved',
                    'purchase_type'   => $purchaseType,
                    'purchase_amount' => $purchaseAmount,
                    'bonus_amount'    => $bonusAmount,
                    'payment_status'  => 'pending',
                ]);

                DB::table('monitor_reports')->where('id', $report->id)->update(['created_at' => $reportedAt]);

                // 紐づく応募のステータスを報告済みに更新
                if ($application && in_array($application->status, ['completed', 'confirming'])) {
                    $application->update(['status' => 'approved']);
                }

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
            '初回費'           => 'cooperation_fee',
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

    public function skipToApplicationHeader(string $content): string
    {
        $content = ltrim($content, "\xEF\xBB\xBF");
        $lines   = preg_split('/\r\n|\r|\n/', $content);
        foreach ($lines as $i => $line) {
            if (str_contains($line, '回答者ID')) {
                return implode("\n", array_slice($lines, $i));
            }
        }
        return $content;
    }

    public function parseCsv(string $content): array
    {
        $content = ltrim($content, "\xEF\xBB\xBF"); // UTF-8 BOM除去

        // fgetcsv を使うことでセル内改行を含むCSVを正しく処理
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $headers = null;
        $rows    = [];
        while (($data = fgetcsv($handle)) !== false) {
            $data = array_map('trim', $data);
            if ($headers === null) {
                // 重複ヘッダーは _2, _3 ... でリネーム（最初の列を優先）
                $seen = [];
                $headers = array_map(function ($h) use (&$seen) {
                    if (!isset($seen[$h])) { $seen[$h] = 1; return $h; }
                    return $h . '_' . (++$seen[$h]);
                }, $data);
                continue;
            }
            if (count($data) !== count($headers)) continue;
            $rows[] = array_combine($headers, $data);
        }

        fclose($handle);
        return $rows;
    }

    public function importCollections(array $rows): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        DB::transaction(function () use ($rows, &$result) {
            foreach ($rows as $i => $row) {
                $line = $i + 2;

                $reportedAtRaw = trim($row['報告日時'] ?? '');
                $reportedAt    = $reportedAtRaw !== '' ? Carbon::parse($reportedAtRaw) : now();

                $ermeId    = $row['回答者ID'] ?? null;
                $name      = preg_replace('/[\s\x{3000}]+/u', '', $row['名前'] ?? $row['回答者名'] ?? '') ?: null;
                $kana      = preg_replace('/[\s\x{3000}]+/u', '', $row['フリガナ'] ?? '') ?: null;
                $itemCount = (int) ($row['商品数'] ?? 0);
                $shipping  = (int) preg_replace('/[^\d]/', '', $row['送料'] ?? '0');
                $tracking  = trim($row['追跡番号'] ?? '');

                if (empty($tracking)) {
                    $result['errors'][] = "{$line}行目: 追跡番号が空です";
                    continue;
                }
                if ($itemCount <= 0) {
                    $result['errors'][] = "{$line}行目: 商品数が不正です";
                    continue;
                }

                // ユーザー特定
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
                    $result['errors'][] = "{$line}行目: ユーザーが特定できません（{$name}）";
                    continue;
                }

                // 重複チェック: ユーザー×報告日時
                if (CollectionReport::where('user_id', $user->id)
                    ->whereDate('created_at', $reportedAt->toDateString())
                    ->exists()) {
                    $result['skipped']++;
                    continue;
                }

                $cr = CollectionReport::create([
                    'user_id'         => $user->id,
                    'campaign_ids'    => [],
                    'tracking_number' => $tracking,
                    'shipping_fee'    => $shipping,
                    'item_count'      => $itemCount,
                    'cooperation_fee' => CollectionReport::calcFee($itemCount, $shipping),
                    'status'          => 'approved',
                ]);

                DB::table('collection_reports')->where('id', $cr->id)->update(['created_at' => $reportedAt]);

                $result['success']++;
            }
        });

        return $result;
    }
}
