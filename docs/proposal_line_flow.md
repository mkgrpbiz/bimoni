# 打診・LINE自動送信 設計書

作成日: 2026-06-24

---

## 全体フロー

```
管理者が「打診中」にする（invited_at を入力必須）
  → proposal_token 生成
  → line_message_jobs: send_type=proposal, send_at=now() で作成
  → スケジューラーが即時送信（打診URL付きLINEメッセージ）

ユーザーが /proposals/{token}/confirm を開く
  ↓
【はい】を選択
  → applications.status = scheduled
  → reserved_at, proposal_answered_at, proposal_answer=yes 保存
  → line_message_jobs: send_type=monitor_guide, send_at=invited_at 作成
  → line_message_jobs: send_type=reminder, send_at=invited_end_at 作成（end_at設定時）
  → /proposals/{token}/complete 表示

【いいえ】を選択
  → available_times から直近2日の候補を2〜4件表示
  → 候補を選ぶ → status=scheduled, invited_at=選択日時 保存
  → monitor_guide / reminder ジョブ作成
  → /proposals/{token}/complete 表示
  → キャンセル → status=cancelled（再応募必要）

【間違えた】を選択（完了画面から）
  → status = line_contacted に戻す
  → pending 状態の monitor_guide / reminder ジョブを canceled に変更
  → /proposals/{token}/confirm に戻す
```

---

## DBテーブル設計

### applications テーブル追加カラム

| カラム名 | 型 | 説明 |
|---|---|---|
| proposal_token | string(64) unique nullable | 打診URL用トークン |
| proposal_answered_at | datetime nullable | ユーザー回答日時 |
| proposal_answer | enum(yes,no) nullable | ユーザー回答内容 |
| proposal_sent_at | datetime nullable | 打診LINE送信日時 |

### campaigns テーブル追加カラム

| カラム名 | 型 | 説明 |
|---|---|---|
| monitor_end_message | text nullable | モニター終了案内文 |

### line_message_jobs テーブル（新規）

| カラム名 | 型 | 説明 |
|---|---|---|
| id | bigint PK | |
| application_id | FK→applications | |
| user_id | FK→users | |
| campaign_id | FK→campaigns | |
| line_user_id | string nullable | 送信先 LINE UID |
| send_type | enum | proposal/monitor_guide/reminder/report_request |
| message_body | text | 送信メッセージ本文 |
| send_at | datetime | 送信予定日時 |
| sent_at | datetime nullable | 実際の送信日時 |
| status | enum | pending/sent/failed/canceled |
| error_message | text nullable | 失敗時エラー内容 |
| timestamps | | |

---

## 送信タイプ別メッセージ

### proposal（打診）
- send_at: 管理者が打診中にした時点（即時）
- 内容: "【モニターご案内】{campaign_title}\n実施日時: {invited_at}\n▼回答URL\n{proposal_url}"

### monitor_guide（案内）
- send_at: invited_at（案件実施予定日時）
- 内容: campaigns.monitor_invite_message（案件ごとに設定）

### reminder（リマインド）
- send_at: invited_end_at（案件実施終了日時）
- 内容: campaigns.monitor_end_message（案件ごとに設定）

---

## スケジューラー

コマンド: `php artisan line:send-messages`
実行間隔: 毎分

対象: `line_message_jobs` で
- status = pending
- send_at <= 現在時刻

LINE トークン未設定（開発環境）: ログ出力のみ、status = sent に更新
LINE トークン設定済み: 実送信後 status = sent / failed 更新

---

## 打診URL

形式: `/proposals/{token}/confirm`
トークン: `Str::random(64)` で生成、applications.proposal_token に保存
認証不要（トークンのみで保護）

---

## 管理画面追加表示項目

案件別応募一覧に追加:
- 打診URL（コピー/開くボタン）
- 打診回答（はい/いいえ/未回答）
- 回答日時
- LINE送信予約状態（pending/sent/failed/canceled）
- 案内文送信状態

打診ボタン変更:
- 現在: 単純な status=line_contacted への PATCH
- 変更後: invited_at / invited_end_at 入力モーダルを経由して打診
