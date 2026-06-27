# BIMONI データ移行設計書

**バージョン:** 1.0  
**最終更新日:** 2026-06-23

> 要件定義 → `BIMONI_requirements.md`
> DB設計 → `BIMONI_db_design.md`

---

## 変更履歴

| 日付 | バージョン | 変更内容 |
|------|-----------|---------|
| 2026-06-23 | 1.0 | 初版作成 |

---

## 1. 移行の全体方針

| 方針 | 内容 |
|------|------|
| 既存データの扱い | エルメ・スプレッドシートのデータをCSVでエクスポートし、BIMONIシステムへインポート |
| ユーザーの主キー | BIMONI独自の `users.id`（AUTO INCREMENT） |
| エルメ回答者ID | `users.erme_respondent_id` に保存。主キーとして使用しない |
| LINE UID | 移行後、各ユーザーがLIFFログインした時点で自動紐付け |
| 移行データの出所管理 | `imported_from` カラムで新規/スプシ移行を区別 |
| 重複ユーザー | 管理画面から手動統合（自動マージは行わない） |

---

## 2. 移行対象データ

| 移行対象 | 移行元 | 移行先テーブル |
|---------|--------|---------------|
| ユーザー情報 | エルメ回答データ / Googleスプレッドシート | `users` |
| 応募履歴 | Googleスプレッドシート | `applications` |
| ポイント（協力金）履歴 | Googleスプレッドシート | `points` |

---

## 3. CSVフォーマット定義

### 3.1 ユーザーインポート用CSV

```
erme_respondent_id, name, name_kana, gender, birthdate, area, available_times, wants_continuation, point_balance
```

| カラム | 型 | 必須 | 説明 |
|-------|-----|------|------|
| erme_respondent_id | 文字列 | 任意 | エルメ回答者ID |
| name | 文字列 | 必須 | 氏名 |
| name_kana | 文字列 | 任意 | フリガナ |
| gender | male/female/other | 任意 | 性別 |
| birthdate | YYYY-MM-DD | 任意 | 生年月日 |
| area | 文字列 | 任意 | 居住エリア |
| available_times | セミコロン区切り | 任意 | 実施可能な時間帯（例: 平日昼;土日） |
| wants_continuation | 1 or 0 | 任意 | 継続希望 |
| point_balance | 整数 | 任意 | 保有ポイント残高（初期値） |

**サンプルCSV:**
```csv
erme_respondent_id,name,name_kana,gender,birthdate,area,available_times,wants_continuation,point_balance
U123456789,山田花子,ヤマダハナコ,female,1990-05-15,東京都,平日昼;土日,1,500
U987654321,田中太郎,タナカタロウ,male,1985-11-22,大阪府,平日夜,0,0
```

---

### 3.2 応募履歴インポート用CSV

```
erme_respondent_id, campaign_name, status, applied_at, selected_at, completed_at, approved_at
```

| カラム | 型 | 必須 | 説明 |
|-------|-----|------|------|
| erme_respondent_id | 文字列 | 必須 | エルメ回答者ID（ユーザー紐付け用） |
| campaign_name | 文字列 | 必須 | 案件名（既存案件との照合に使用） |
| status | 文字列 | 必須 | ステータス（後述の変換テーブルで対応） |
| applied_at | YYYY-MM-DD HH:MM:SS | 任意 | 応募日時 |
| selected_at | YYYY-MM-DD HH:MM:SS | 任意 | 当選日時 |
| completed_at | YYYY-MM-DD HH:MM:SS | 任意 | 完了日時 |
| approved_at | YYYY-MM-DD HH:MM:SS | 任意 | 承認日時 |

---

### 3.3 ポイント履歴インポート用CSV

```
erme_respondent_id, type, amount, reason, granted_at
```

| カラム | 型 | 必須 | 説明 |
|-------|-----|------|------|
| erme_respondent_id | 文字列 | 必須 | エルメ回答者ID |
| type | earn/exchange/adjust | 必須 | 種別 |
| amount | 整数 | 必須 | 金額（マイナスも可） |
| reason | 文字列 | 任意 | 理由 |
| granted_at | YYYY-MM-DD | 必須 | 付与日 |

---

## 4. インポート処理の設計

### 4.1 処理フロー

```
① 管理者がCSVをアップロード（A-16 データインポート画面）
        ↓
② バリデーション
   ・必須カラムの存在確認
   ・データ型チェック
   ・重複エルメIDの検出
        ↓
③ 確認画面表示
   ・インポート予定件数
   ・エラー・警告一覧
   ・重複候補一覧
        ↓
④ 管理者が確認して「実行」ボタンを押す
        ↓
⑤ インポート処理実行
   ・users テーブルへ INSERT（erme_respondent_id で重複チェック）
   ・applications テーブルへ INSERT（erme_respondent_id → user_id で紐付け）
   ・points テーブルへ INSERT
   ・imported_from = 'spreadsheet' でフラグ付け
        ↓
⑥ 結果レポート表示
   ・成功件数 / スキップ件数 / エラー件数
   ・エラー詳細一覧（行番号 + 理由）
```

### 4.2 重複ユーザーの処理ルール

| ケース | 処理 |
|--------|------|
| 同じ erme_respondent_id が既に存在する | スキップ（上書きしない）＋警告表示 |
| erme_respondent_id が空のユーザー | 新規として INSERT |
| 同名・同生年月日の別レコードが存在する | 警告表示（手動統合を促す） |

---

## 5. LINE UIDとの紐付け

### 5.1 移行ユーザーのLINE UID紐付けフロー

```
移行済みユーザー（line_user_id = NULL の状態）
        ↓
LIFFログインを促す（LINE公式アカウントからLIFF URLを案内）
        ↓
ユーザーがLIFFを開いてLINEログイン
        ↓
LIFF認証処理
        ↓
【LINE UID = NULL のユーザー紐付け処理】
  line_user_id が新規 AND erme_respondent_id が入力された場合
      → erme_respondent_id で users テーブルを検索
      → 見つかった → line_user_id を更新して紐付け完了
      → 見つからない → 新規ユーザーとして登録
```

### 5.2 管理画面での手動紐付け

- A-09（ユーザー詳細）画面から管理者が手動で LINE UID を入力・紐付け可能
- 移行ユーザー一覧（line_user_id = NULL）を絞り込み表示して確認できる

---

## 6. 重複ユーザー統合の設計

### 6.1 統合が必要なケース

- 同一人物がエルメ・スプレッドシートに複数レコードで存在する場合
- 移行後に新規登録してしまい、移行データと重複した場合

### 6.2 統合処理の流れ（管理画面）

```
A-09 ユーザー詳細 → 「このユーザーと統合」ボタン
        ↓
統合元ユーザーID（残す）と統合先ユーザーID（削除する）を選択
        ↓
確認画面：統合後の状態をプレビュー表示
        ↓
実行：
  ① 統合先のapplicationsをすべて統合元のuser_idに書き換え
  ② 統合先のpointsをすべて統合元のuser_idに書き換え
  ③ 統合元のpoint_balanceに統合先の残高を加算
  ④ 統合元にerme_respondent_idが未設定なら統合先のIDを移植
  ⑤ 統合先のユーザーを削除またはsuspended状態に変更
```

---

## 7. 段階的移行計画

### ステップ 1: 新システム構築（Phase 1〜5 完了後）

- BIMONIシステムの新機能を本番環境にデプロイ
- まだエルメ・スプレッドシートと並行運用

### ステップ 2: ユーザーデータ移行

1. エルメ・スプレッドシートからユーザーCSVをエクスポート
2. A-16 からユーザーインポートを実行
3. 管理者がインポート結果を確認・重複を統合

### ステップ 3: 既存ユーザーへLIFF案内

1. LINE公式アカウントから既存フォロワーへLIFF URLを送信
2. ユーザーがLIFFログイン → LINE UID が自動紐付け
3. プロフィール確認・更新を促す

### ステップ 4: 過去データ移行

1. 応募履歴CSVをインポート（参照用・集計用）
2. ポイント履歴CSVをインポート（残高の整合性確認）

### ステップ 5: 旧ツールの段階的廃止

1. 新規応募をBIMONIシステムのみで受付開始
2. 新規ポイント付与をBIMONIシステムのみで実施
3. エルメ応募フォームを閉鎖
4. スプレッドシート管理を終了

---

## 8. バックアップ・ロールバック方針

| 項目 | 対応 |
|------|------|
| インポート前のバックアップ | DBダンプを取得してからインポート実行 |
| インポート失敗時 | トランザクションでロールバック（1件でもエラーがある場合は全件ロールバック） |
| 部分インポート | imported_from = 'spreadsheet' のデータのみ一括削除して再インポート可能 |
| 旧スプレッドシート | 移行完了後も最低6ヶ月は保管（参照用） |
