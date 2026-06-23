# 応募管理 再設計書

作成日: 2026-06-24

## 概要

BIMONIの応募管理を実運用に合わせて再設計する。
主な変更点は案件別管理・日別打診予定数・48時間制限・他案件ステータス反映・ステータス履歴管理。

---

## ステータスフロー

```
応募（applied / pending）
  ↓ 運営が打診対象を選択
打診中（sounding / line_contacted）  ← LINE送信済み
  ↓ ユーザーが実施可能と回答
予約中（reserved / scheduled）
  ↓ 案内予定日時に案件案内文を自動送信（LINE API）
  ↓ 案内終了時間にリマインド自動送信（LINE API）
実施確認中（confirming）  ← 新規追加
  ↓
実施完了（completed）
  ↓
報告（reported）
  ↓
承認（approved）
  ↓
協力金付与（point_granted）
```

キャンセル（cancelled）はどのステータスからも遷移可能。

---

## DBテーブル設計

### 既存テーブル変更

#### applications（カラム追加・ENUM拡張）

追加カラム:
| カラム名 | 型 | 説明 |
|---|---|---|
| sounded_at | datetime nullable | 打診送信日時 |
| reserved_at | datetime nullable | 予約確定日時 |
| monitoring_confirmed_at | datetime nullable | 実施確認移行日時 |
| invited_at | datetime nullable | 案内予定日時（LINE自動送信トリガー） |
| invited_end_at | datetime nullable | 案内終了日時（リマインド送信トリガー） |
| continuation_invite_date | date nullable | 継続案内予定日 |

ENUM追加: `confirming`（実施確認中）

#### campaigns（カラム追加）

追加カラム:
| カラム名 | 型 | 説明 |
|---|---|---|
| monitor_invite_message | text nullable | モニター案内文（LINE自動送信用） |
| target_male_ratio | unsignedTinyInteger nullable | 目標男性比率（%） |
| target_female_ratio | unsignedTinyInteger nullable | 目標女性比率（%） |

### 新規テーブル

#### campaign_daily_slots（日別打診予定数）

| カラム名 | 型 | 説明 |
|---|---|---|
| id | bigint PK | |
| campaign_id | FK → campaigns | |
| target_date | date | 対象日 |
| planned_count | unsignedInteger default 0 | 打診予定数 |
| invited_count | unsignedInteger default 0 | 打診済数（line_contacted） |
| reserved_count | unsignedInteger default 0 | 予約済数 |
| completed_count | unsignedInteger default 0 | 実施完了数 |
| memo | text nullable | |
| timestamps | | |

UNIQUE: (campaign_id, target_date)

#### application_status_logs（ステータス変更履歴）

| カラム名 | 型 | 説明 |
|---|---|---|
| id | bigint PK | |
| application_id | FK → applications | |
| from_status | varchar nullable | 変更前ステータス |
| to_status | varchar | 変更後ステータス |
| changed_by | FK → admins nullable | 変更者管理者ID |
| memo | text nullable | |
| timestamps | | |

---

## 案件別応募管理画面

URL: `/admin/campaigns/{campaign}/applications`

### ヘッダー情報（上部集計エリア）

- 当日・翌日・翌々日の目標件数（campaign_daily_slots.planned_count）
- 案件設定の男女比目標
- 当日・翌日・翌々日の案内件数（sounded_at の日付集計）
- 実施完了の男女比・継続率

### 一覧表示項目

| 項目 | ソース |
|---|---|
| 応募日 | applied_at |
| 回答者ID | users.erme_respondent_id |
| LINE UID | users.line_user_id |
| 名前 | users.name |
| フリガナ | users.name_kana |
| 年齢 | users.birthdate から計算 |
| 性別 | users.gender |
| 継続可否 | users.wants_continuation |
| 実施可能時間帯 | users.available_times |
| ステータス | applications.status |
| 案内予定日時 | applications.invited_at |
| 継続案内日 | applications.continuation_invite_date |
| 他案件ステータス | 同一ユーザーの他案件を検索 |
| 48時間制限状態 | 他案件のcompleted_atから計算 |

---

## 48時間制限ロジック

判定順序:
1. LINE UID が一致
2. エルメ回答者IDが一致
3. 電話番号が一致
4. メールアドレスが一致

いずれかで同一ユーザーと判定された場合、他案件のcompleted_atから48時間以内であれば打診不可。

表示例: `6/25 14:00〜打診可能`

---

## 案内ロック条件

以下ステータスのユーザーは他案件で打診不可:
- 打診中（line_contacted）
- 予約中（scheduled）
- 実施確認中（confirming）
- 実施完了後48時間以内（completed + 48h check）

---

## 日別打診予定数管理

URL: `/admin/campaigns/{campaign}/daily-slots`

- 日付と予定件数の一覧表示
- 追加・編集・削除
- CSVインポート形式: `日付,件数` 例: `6/24,10`

---

## LINE自動送信設計（将来実装）

予約中に移行したタイミングで`invited_at`を記録。
スケジューラー（`app/Console/Commands/SendScheduledInvites.php`）が
`invited_at`を過ぎた予約中の応募に対してLINE API送信を実行する。

実装時に使用するカラム:
- `campaigns.monitor_invite_message` → 送信メッセージ本文
- `applications.invited_at` → 送信トリガー日時
- `applications.invited_end_at` → リマインド送信トリガー日時
