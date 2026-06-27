# BIMONI DB設計書

**バージョン:** 2.0  
**最終更新日:** 2026-06-23  
**DBMS:** MySQL 8.0

> 要件定義 → `BIMONI_requirements.md`

---

## 変更履歴

| 日付 | バージョン | 変更内容 |
|------|-----------|---------|
| 2026-06-23 | 1.0 | 初版作成 |
| 2026-06-23 | 2.0 | LINE LIFF前提・案件種別・応募フロー・移行対応で全面再設計 |

---

## 1. テーブル一覧

| テーブル名 | 概要 | Phase |
|-----------|------|-------|
| `users` | モニター（一般ユーザー）情報 | 1 |
| `admins` | 管理者（運営スタッフ）情報 | 1 |
| `categories` | 案件カテゴリ | 2 |
| `tags` | 案件タグ | 2 |
| `campaigns` | 案件情報 | 2 |
| `campaign_tag` | 案件とタグの中間テーブル | 2 |
| `applications` | 応募情報 | 3 |
| `application_schedules` | 打診・日程調整 | 3 |
| `monitor_reports` | モニター報告 | 4 |
| `monitor_report_images` | 報告画像 | 4 |
| `points` | ポイント（協力金）履歴 | 5 |
| `point_settlements` | 月末締め管理 | 5 |
| `point_exchanges` | ポイント交換申請 | 5 |
| `line_notifications` | LINE通知送信ログ | 6 |

---

## 2. テーブル定義

---

### users（モニター）

ユーザーの主キーはBIMONI独自の `id`。LINE UIDを一意の認証識別子とする。

| カラム名 | 型 | NOT NULL | デフォルト | 説明 |
|---------|-----|---------|----------|------|
| id | BIGINT UNSIGNED | ✓ | AUTO_INCREMENT | PK |
| line_user_id | VARCHAR(255) | ✓ | | LINE UID（UNIQUE） |
| erme_respondent_id | VARCHAR(255) | | NULL | エルメ回答者ID（移行用外部ID） |
| name | VARCHAR(100) | | NULL | 氏名 |
| name_kana | VARCHAR(100) | | NULL | フリガナ |
| gender | ENUM('male','female','other') | | NULL | 性別 |
| birthdate | DATE | | NULL | 生年月日 |
| area | VARCHAR(100) | | NULL | 居住エリア |
| available_times | JSON | | NULL | 実施可能な時間帯（例: ["平日昼","土日"]） |
| wants_continuation | TINYINT(1) | | NULL | 継続希望（1=希望, 0=不希望） |
| point_balance | INT | ✓ | 0 | 保有ポイント（協力金残高） |
| status | ENUM('active','suspended') | ✓ | 'active' | アカウント状態 |
| profile_completed_at | TIMESTAMP | | NULL | プロフィール登録完了日時 |
| imported_from | ENUM('new','spreadsheet','erme') | ✓ | 'new' | データ出所（移行管理用） |
| created_at | TIMESTAMP | | NULL | |
| updated_at | TIMESTAMP | | NULL | |

**インデックス:**
- `line_user_id` UNIQUE
- `erme_respondent_id` INDEX（移行時の検索用）

---

### admins（管理者）

| カラム名 | 型 | NOT NULL | デフォルト | 説明 |
|---------|-----|---------|----------|------|
| id | BIGINT UNSIGNED | ✓ | AUTO_INCREMENT | PK |
| name | VARCHAR(100) | ✓ | | 氏名 |
| email | VARCHAR(255) | ✓ | | メールアドレス（UNIQUE） |
| password | VARCHAR(255) | ✓ | | ハッシュ化パスワード |
| remember_token | VARCHAR(100) | | NULL | |
| created_at | TIMESTAMP | | NULL | |
| updated_at | TIMESTAMP | | NULL | |

---

### categories（カテゴリ）

| カラム名 | 型 | NOT NULL | 説明 |
|---------|-----|---------|------|
| id | BIGINT UNSIGNED | ✓ | PK |
| name | VARCHAR(100) | ✓ | カテゴリ名（例：エステ、ネイル、脱毛） |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

---

### tags（タグ）

| カラム名 | 型 | NOT NULL | 説明 |
|---------|-----|---------|------|
| id | BIGINT UNSIGNED | ✓ | PK |
| name | VARCHAR(100) | ✓ | タグ名 |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

---

### campaigns（案件）

| カラム名 | 型 | NOT NULL | デフォルト | 説明 |
|---------|-----|---------|----------|------|
| id | BIGINT UNSIGNED | ✓ | AUTO_INCREMENT | PK |
| category_id | BIGINT UNSIGNED | | NULL | FK → categories.id |
| title | VARCHAR(255) | ✓ | | 案件名 |
| campaign_type | ENUM('experience','product','recovery') | ✓ | | 体験 / 商品 / 回収 |
| status | ENUM('draft','published','closed') | ✓ | 'draft' | 下書き / 公開 / 終了 |
| pr_media | VARCHAR(255) | | NULL | PR媒体 |
| description | TEXT | | NULL | 案件内容説明 |
| requirements | TEXT | | NULL | 応募条件 |
| notes | TEXT | | NULL | 注意事項（解約・回収・その他） |
| product_name | VARCHAR(255) | | NULL | 商品名（商品・回収モニター用） |
| product_price | INT UNSIGNED | | NULL | 商品金額（円） |
| cooperation_fee | INT UNSIGNED | ✓ | 0 | モニター協力金（円） |
| referral_fee | INT UNSIGNED | ✓ | 0 | 紹介単価（円） |
| campaign_unit_price | INT UNSIGNED | | NULL | 案件単価（運営売上） |
| initial_purchase_fee | INT UNSIGNED | | NULL | 初回購入費（円） |
| recurring_purchase_fee | INT UNSIGNED | | NULL | 継続購入費（円） |
| gross_profit | INT | | NULL | 粗利（管理者のみ閲覧） |
| continuation_rate | DECIMAL(5,2) | | NULL | 継続率（%） |
| target_gender_ratio | VARCHAR(50) | | NULL | 男女比の目標（例："男:女=3:7"） |
| capacity | INT UNSIGNED | ✓ | 1 | 募集人数 |
| solicitation_target | INT UNSIGNED | | NULL | 打診予定数 |
| thumbnail | VARCHAR(255) | | NULL | サムネイル画像パス |
| application_start_at | DATE | | NULL | 募集開始日 |
| application_end_at | DATE | | NULL | 募集終了日 |
| created_by | BIGINT UNSIGNED | | NULL | FK → admins.id |
| created_at | TIMESTAMP | | NULL | |
| updated_at | TIMESTAMP | | NULL | |

**インデックス:** `status`, `campaign_type`, `application_end_at`

---

### campaign_tag（案件タグ中間テーブル）

| カラム名 | 型 | NOT NULL | 説明 |
|---------|-----|---------|------|
| campaign_id | BIGINT UNSIGNED | ✓ | FK → campaigns.id |
| tag_id | BIGINT UNSIGNED | ✓ | FK → tags.id |

**PK:** (campaign_id, tag_id)

---

### applications（応募）

| カラム名 | 型 | NOT NULL | デフォルト | 説明 |
|---------|-----|---------|----------|------|
| id | BIGINT UNSIGNED | ✓ | AUTO_INCREMENT | PK |
| user_id | BIGINT UNSIGNED | ✓ | | FK → users.id |
| campaign_id | BIGINT UNSIGNED | ✓ | | FK → campaigns.id |
| status | ENUM(...) ※下記 | ✓ | 'pending' | 応募ステータス |
| line_contact_status | ENUM('not_sent','sent','confirmed') | ✓ | 'not_sent' | LINE案内状況 |
| applied_at | TIMESTAMP | ✓ | CURRENT_TIMESTAMP | 応募日時 |
| selected_at | TIMESTAMP | | NULL | 当選確定日時 |
| line_contacted_at | TIMESTAMP | | NULL | LINE案内送信日時 |
| schedule_confirmed_at | TIMESTAMP | | NULL | 日程確定日時 |
| completed_at | TIMESTAMP | | NULL | 実施完了日時 |
| reported_at | TIMESTAMP | | NULL | 報告投稿日時 |
| approved_at | TIMESTAMP | | NULL | 報告承認日時 |
| notes | TEXT | | NULL | 管理メモ |
| imported_from | ENUM('new','spreadsheet') | ✓ | 'new' | データ出所 |
| created_at | TIMESTAMP | | NULL | |
| updated_at | TIMESTAMP | | NULL | |

**statusのENUM値:**
```
'pending'        - 審査中
'selected'       - 当選
'rejected'       - 落選
'line_contacted' - LINE案内済み
'scheduled'      - 日程確定
'completed'      - 実施完了
'reported'       - 報告済み
'approved'       - 承認済み（協力金付与待ち）
'point_granted'  - 協力金付与済み
'cancelled'      - キャンセル
```

**インデックス:** (user_id, campaign_id) UNIQUE

---

### application_schedules（打診・日程調整）

| カラム名 | 型 | NOT NULL | デフォルト | 説明 |
|---------|-----|---------|----------|------|
| id | BIGINT UNSIGNED | ✓ | AUTO_INCREMENT | PK |
| application_id | BIGINT UNSIGNED | ✓ | | FK → applications.id |
| proposed_dates | JSON | | NULL | 打診候補日時リスト |
| confirmed_datetime | DATETIME | | NULL | 確定した日時 |
| status | ENUM('proposing','confirmed','cancelled') | ✓ | 'proposing' | 打診ステータス |
| proposed_by | BIGINT UNSIGNED | | NULL | FK → admins.id（打診した管理者） |
| notes | TEXT | | NULL | |
| created_at | TIMESTAMP | | NULL | |
| updated_at | TIMESTAMP | | NULL | |

---

### monitor_reports（モニター報告）

| カラム名 | 型 | NOT NULL | デフォルト | 説明 |
|---------|-----|---------|----------|------|
| id | BIGINT UNSIGNED | ✓ | AUTO_INCREMENT | PK |
| application_id | BIGINT UNSIGNED | ✓ | | FK → applications.id（UNIQUE） |
| user_id | BIGINT UNSIGNED | ✓ | | FK → users.id |
| campaign_id | BIGINT UNSIGNED | ✓ | | FK → campaigns.id |
| report_body | TEXT | | NULL | 報告テキスト |
| status | ENUM('pending','approved','rejected') | ✓ | 'pending' | 承認状態 |
| reviewed_by | BIGINT UNSIGNED | | NULL | FK → admins.id |
| reviewed_at | TIMESTAMP | | NULL | |
| reject_reason | TEXT | | NULL | 差戻し理由 |
| created_at | TIMESTAMP | | NULL | |
| updated_at | TIMESTAMP | | NULL | |

---

### monitor_report_images（報告画像）

| カラム名 | 型 | NOT NULL | 説明 |
|---------|-----|---------|------|
| id | BIGINT UNSIGNED | ✓ | PK |
| monitor_report_id | BIGINT UNSIGNED | ✓ | FK → monitor_reports.id |
| image_path | VARCHAR(255) | ✓ | 画像保存パス |
| sort_order | TINYINT UNSIGNED | ✓ | 表示順 |
| created_at | TIMESTAMP | | |

---

### points（ポイント・協力金履歴）

| カラム名 | 型 | NOT NULL | デフォルト | 説明 |
|---------|-----|---------|----------|------|
| id | BIGINT UNSIGNED | ✓ | AUTO_INCREMENT | PK |
| user_id | BIGINT UNSIGNED | ✓ | | FK → users.id |
| type | ENUM('earn','exchange','adjust','cancel') | ✓ | | 獲得/交換/調整/取消 |
| amount | INT | ✓ | | 変動額（マイナスあり） |
| reason | VARCHAR(255) | | NULL | 付与・消費理由 |
| application_id | BIGINT UNSIGNED | | NULL | FK → applications.id |
| settlement_id | BIGINT UNSIGNED | | NULL | FK → point_settlements.id |
| granted_by | BIGINT UNSIGNED | | NULL | FK → admins.id（付与した管理者） |
| imported_from | ENUM('new','spreadsheet') | ✓ | 'new' | データ出所 |
| created_at | TIMESTAMP | | NULL | |

---

### point_settlements（月末締め管理）

| カラム名 | 型 | NOT NULL | デフォルト | 説明 |
|---------|-----|---------|----------|------|
| id | BIGINT UNSIGNED | ✓ | AUTO_INCREMENT | PK |
| settlement_month | DATE | ✓ | | 締め月（月末日、例: 2026-06-30） |
| payment_due_date | DATE | ✓ | | 支払予定日（翌月10日） |
| status | ENUM('open','closed','paid') | ✓ | 'open' | 未締め / 締め済み / 支払済み |
| total_amount | INT UNSIGNED | ✓ | 0 | 締め対象の合計ポイント数 |
| closed_by | BIGINT UNSIGNED | | NULL | FK → admins.id |
| closed_at | TIMESTAMP | | NULL | 締め処理日時 |
| created_at | TIMESTAMP | | NULL | |
| updated_at | TIMESTAMP | | NULL | |

---

### point_exchanges（ポイント交換申請）

| カラム名 | 型 | NOT NULL | デフォルト | 説明 |
|---------|-----|---------|----------|------|
| id | BIGINT UNSIGNED | ✓ | AUTO_INCREMENT | PK |
| user_id | BIGINT UNSIGNED | ✓ | | FK → users.id |
| points | INT UNSIGNED | ✓ | | 交換ポイント数 |
| exchange_type | VARCHAR(100) | ✓ | | 交換先（例：Amazonギフト券） |
| status | ENUM('pending','approved','rejected') | ✓ | 'pending' | |
| processed_by | BIGINT UNSIGNED | | NULL | FK → admins.id |
| processed_at | TIMESTAMP | | NULL | |
| created_at | TIMESTAMP | | NULL | |
| updated_at | TIMESTAMP | | NULL | |

---

### line_notifications（LINE通知送信ログ）

| カラム名 | 型 | NOT NULL | デフォルト | 説明 |
|---------|-----|---------|----------|------|
| id | BIGINT UNSIGNED | ✓ | AUTO_INCREMENT | PK |
| user_id | BIGINT UNSIGNED | ✓ | | FK → users.id |
| application_id | BIGINT UNSIGNED | | NULL | FK → applications.id |
| notification_type | ENUM('applied','selected','schedule','report_request','point_granted','general') | ✓ | | 通知種別 |
| message | TEXT | ✓ | | 送信メッセージ |
| status | ENUM('sent','failed') | ✓ | 'sent' | 送信状態 |
| sent_at | TIMESTAMP | ✓ | CURRENT_TIMESTAMP | |

---

## 3. ER図（テキスト表現）

```
users ─────────────────────────────────────── applications
  │                                                  │
  │                                    application_schedules
  │
  └── points ──────────── point_settlements
  └── point_exchanges
  └── line_notifications

applications ──── monitor_reports ──── monitor_report_images

campaigns ──── campaign_tag ──── tags
campaigns ──── categories
campaigns ──── applications
```

### 主なリレーション

| 親 | 子 | 関係 |
|---|---|------|
| users | applications | 1対多 |
| campaigns | applications | 1対多 |
| applications | application_schedules | 1対多 |
| applications | monitor_reports | 1対1 |
| monitor_reports | monitor_report_images | 1対多 |
| users | points | 1対多 |
| point_settlements | points | 1対多 |
| campaigns | campaign_tag | 多対多（tags経由） |

---

## 4. LINE UID管理の設計

### 4.1 ユーザー識別の優先順位

```
LINE LIFF ログイン
    ↓
LINE UID（line_user_id）を取得
    ↓
users テーブルで line_user_id を検索
    ↓
【既存ユーザー】→ ログイン成功・セッション開始
【新規ユーザー】→ プロフィール登録画面へ
                   └→ エルメ回答者IDで既存データ検索
                         ├→ 見つかった → 既存データとLINE UIDを紐付け
                         └→ 見つからない → 新規ユーザーとして登録
```

### 4.2 エルメ回答者IDの扱い

- `users.erme_respondent_id` に保存（NULL許可）
- 主キーでも認証IDでもない
- 移行時の名寄せ・重複統合に使用
- 将来的に不要になっても残してよい（削除不要）

---

## 5. 案件種別ごとのステータス遷移

### 体験モニター（experience）
```
pending → selected → line_contacted → scheduled → completed → reported → approved → point_granted
```

### 商品モニター（product）
```
pending → selected → line_contacted → completed → reported → approved → point_granted
※ 日程調整不要（商品発送ベース）
```

### 回収サービス（recovery）
```
pending → selected → line_contacted → completed → reported → approved → point_granted
※ 返送確認後に報告
```
