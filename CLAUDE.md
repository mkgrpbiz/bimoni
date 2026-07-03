# BIMONI 開発メモ

## プロジェクト概要
美容モニターキャンペーン管理システム。代理店が招待コードでユーザーを集め、ユーザーがモニター案件に応募・報告する。

## 技術スタック
- **フレームワーク**: Laravel 11 (Blade, Eloquent)
- **認証**: LINE LIFF（会員）/ セッション（管理画面・ポータル）
- **ローカル環境**: Laragon (`C:\laragon\www\bimoni`)
- **本番/STG**: Xserver (`sv16576`)

---

## サーバー・デプロイ

### SSH接続
```bash
ssh -i "$env:USERPROFILE\.ssh\xserver.key" -p 10022 mkgrp@sv16576.xserver.jp
```

### デプロイ手順（STGサーバー）
自動デプロイは廃止（GitHub Actionsのワークフローは削除済み）。mainにpushしたあと、毎回SSHで手動デプロイが必要：
```bash
cd /home/mkgrp/bimoni
git pull
php8.3 artisan migrate --force
php8.3 artisan view:clear
php8.3 artisan route:clear
```
> STGのURLは `https://stg.bimoni.online`（本番 `bimoni.online` とは別。本番に反映するには本番サーバーでも別途git pull等が必要）
> **注意**: サーバーのデフォルトPHPは8.0。必ず `php8.3` を使う。
> STGのcronは `schedule:run` を毎分実行。`proposals:auto-cancel`（5分ごと）と `line:send-messages`（毎分）が動く。
> cron設定: `/opt/php-8.3/bin/php /home/mkgrp/bimoni/artisan schedule:run`

### Git（ローカル）
```powershell
& "C:\laragon\bin\git\cmd\git.exe" add -A
& "C:\laragon\bin\git\cmd\git.exe" commit -m "メッセージ"
& "C:\laragon\bin\git\cmd\git.exe" push origin main
```
> Laragonのgitはシステムパスに入っていないのでフルパス指定が必要。

---

## 主要モデル・DB設計

### User
- `bimoni_user_id`: 独自ID（例: BMN010001）。BMN + 6桁、10001スタート、登録順自動採番
- `referred_by_code`: 登録時に使った招待コード（どのコードから来たか）
- `referral_code` は**廃止**。ユーザー個人の紹介コードは存在しない

### Agent（代理店）
- 親子構造（`parent_id`）。親は子を複数持てる
- `getAllCodeStrings()`: 自分＋子の全コード文字列を返す
- `portalUrl()`: ポータルログインURL

### AgentReferralCode（招待コード）
- 代理店に紐づく招待コード（1代理店複数持てる）
- 登録者がいない場合のみ削除可能

### ReferralPaymentStatus
- 月次の紹介報酬支払い状況を管理

### CollectionReport（回収報告）
- `cooperation_fee`: 自動計算（`calcFee($itemCount, $shippingFee)`: 800円×商品数、4個以下は送料を差し引き）
- `tracking_number`: 追跡番号（重複スキップキー）
- `box_image` / `label_image`: 添付画像パス（null許容）
- `estimated_arrival_date`: 到着予定日（null許容、`?->format()` でnull安全に）

### Campaign
- `collection_requirement`: ENUM('回収前提', '回収不要') nullable
  - 条件チェックは必ず `=== '回収前提'`（truthy判定だと'回収不要'でも引っかかる）
  - 回収前提の場合、応募フォームに警告メッセージ表示

---

## LINE LIFF

### 招待ページ（`resources/views/invite.blade.php`）
- 「LINEで登録する」ボタンは `line://app/{LIFF_ID}?referral_code={code}` で直接LINEアプリを開く
- 招待コードをURLパラメータで渡すので紹介元の追跡が継続される
- モーダルは廃止（`line://browser?url=` は動かなかった）
- `liff.login()` に `botPrompt` は設定しない（友だち追加はメンバーページのモーダルで行う）

### LINE友だち未追加モーダル（`resources/views/layouts/member.blade.php`）
- ログイン済みユーザーに対し `liff.getFriendship()` で友だち追加状態を確認
- 未追加の場合はモーダルを表示して `@204zmull` へ誘導
- **登録フォームページ（`member.register*`）は除外**（登録前に飛ばされると導線が崩れるため）
- **必須設定**: LINEデベロッパーコンソールのLIFFアプリで「Add friend option」をONにして `@204zmull` を紐付けること（これがないと全員に表示される）

---

## ポータル（代理店ポータル）

### 共通フィルター（ユーザー管理・報告管理・報酬管理）
- 累計 / 月次 トグル（**デフォルトは月次**）
- 子代理店フィルター（親代理店のみ表示）
- コード別プルダウン（コードが複数ある場合）
- ユーザー一覧は `profile_completed_at` not null のみ表示（LIFF登録だけのユーザーを除外）

### ページ構成
| ページ | 内容 |
|--------|------|
| ユーザー管理 | 登録者一覧・集計（登録/応募/報告数） |
| 報告管理 | 承認済み報告一覧 |
| 報酬管理 | 案件別報酬集計 |
| 子代理店管理 | 子代理店一覧・コード管理（親のみ） |

### 子代理店作成
- 代理店名・報酬設定・招待コード（任意/空欄=自動生成）
- コード追加・削除（登録者がいない場合のみ削除可）

---

## 管理画面

### 代理店管理
- 一覧: 代理店名・子代理店数・コード数・登録数・応募数・報告数・詳細/削除
- 削除: 登録者がいない代理店のみ可（子代理店・コードも一括削除）

### 紹介報酬管理
- 月次の報酬一覧・承認/支払い処理
- 詳細: 代理店のコード別登録者・承認済み報告を表示

### ユーザー管理
- 登録コード（`referred_by_code`）を表示

### 報告管理（`admin/reports`）
- 一覧列: 報告日時 / ユーザーID(`bimoni_user_id`) / 登録コード / LINE表示名 / 名前 / フリガナ / 案件名 / モニター協力金 / ステータス / 詳細
- 詳細: 画像クリックでライトボックス拡大、承認・差戻しアクションあり

### 回収管理（`admin/collection_reports`）
- 一覧列: 報告日時 / ユーザーID(`bimoni_user_id`) / 登録コード / LINE表示名 / 名前 / フリガナ / 商品数 / 到着予定日 / 追跡番号 / ステータス / 詳細
- 詳細: 段ボール画像・発送伝票画像をライトボックスで拡大、承認・差戻しアクションあり

### 紹介報酬詳細
- 承認報告があるユーザーのみ表示（`$activeUsers`）
- 戻るボタンのルートは `['year' => $month->year, 'month' => $month->month]`（`'month' => 'Y-m'` 形式はNG→500エラー）
- 期待報酬0円の場合は「処理不要」バッジ表示
- 承認ユーザー一覧CSVダウンロード機能あり（`referrals.csv` ルート）

### インポート機能
- **応募リストインポート**: エルメのCSVをそのまま投入。案件を選択してインポート
  - `ImportService::skipToApplicationHeader()` でサマリー行（集計情報）を自動スキップし「回答者ID」を含む行をヘッダーとして使用
  - `parseCsv()` で重複ヘッダーは `_2` `_3` でリネーム（最初の列を優先）
  - `normalizeApplicationRows()` で `ステータス共有` 列は明示的にスキップ（列名がそのままでも読み込み対象外）
  - 重複チェック: 同一ユーザー × 同一応募日時 → 上書き更新（この定義は触らない）
  - ステータスマッピング: 実施完了→completed / 実施確認中→confirming / キャンセル→cancelled / 予約中→scheduled / 打診中→line_contacted / 空欄→pending
  - `invited_at`: 採用日+採用時間 または 案内日+案内時間 から設定
  - `available_times`: 購入可能時間を選択 列から設定（いつでもOK含む）。既存ユーザーも更新
  - `continuation_flag`: 継続 / 奨学 列の TRUE/FALSE → possible/not_possible
  - `campaign_name`: ｷｬﾝﾍﾟｰﾝ（半角）/ キャンペーン（全角）両対応
  - applications テーブルの `(user_id, campaign_id)` unique制約は削除済み（同一ユーザーが複数回応募可）
  - 案件別インポートデータ削除スクリプト: `php8.3 fix_delete_campaign_applications.php {campaign_id}`
- **報告インポート**: 列 = 回答者ID, 回答者名（任意）, 名前, フリガナ, 案件名, 初回か継続, モニター経費, キャンペーン
  - ステータスは常に `approved`
  - キャンペーン列に値があれば `bonus_amount=300`
  - 重複チェック: ユーザー×案件×報告日時（同日同案件同ユーザーはスキップ）
  - `purchase_amount` = モニター経費（¥・カンマ除去）
  - `cooperation_fee` 列はMonitorReportに存在しない（Campaignから取得するため保存しない）
  - 応募管理とは完全に切り離し（application_id は既存応募があれば紐付け、なければ null）
- **回収インポート**: 列 = 回答者ID, 回答者名, 名前, フリガナ, 商品数, 送料, 追跡番号
  - 重複チェック: ユーザー×報告日時（同日同ユーザーはスキップ）
  - 協力金は `CollectionReport::calcFee()` で自動計算（800円×商品数、4個以下は送料を差し引き）
  - ステータスは `approved`

---

## 報酬計算ロジック（PortalService::calcReward）
- **親代理店**: `campaign.referral_fee` をそのまま受け取る
- **子代理店**: 親が設定した `child_reward_{fee}` を受け取る（差額が親の利益）

---

## 打診（proposal）フロー

### ステータス遷移
`line_contacted` → 承諾: `scheduled` / 拒否+別日程: `scheduled` / 拒否+キャンセル: `cancelled`
`scheduled` → 実施: `confirming` → `completed` → 報告: `reported` → 承認: `approved` → `point_granted`

### 自動キャンセル
- `proposals:auto-cancel` コマンド（5分ごと）が処理
- 通常打診: `invited_at <= now()` で未回答 → `cancelled`
- PR打診: `invited_end_at <= now()` で未回答 → `cancelled`（`invited_at` は null）
- `proposal_answer` は ENUM('yes', 'no', 'expired')

### 打診ページ（`/proposals/{token}`）
- 期限切れ・無効なリンクは全て「このリンクは無効になりました」ページ（410）
- キャンセル（「いいえ」→断る）に48h制限・他案件ロックなし
- 「いいえ」→別日程選択にも制限なし（管理画面側での打診送信時のみロックチェック）

---

## 注意事項・過去のミス

- `alert()` を Promise の `.then()/.catch()` 内で呼ぶとブラウザにブロックされる → `document.execCommand('copy')` で同期コピー後に `alert()` を呼ぶ
- コピーボタンは必ず同期処理 + `alert('コピーしました')` のセットで実装
- SSHの秘密鍵は `C:\Users\user\.ssh\xserver.key`
- STGのDBをtinker経由で操作するときは、PowerShellからの直接実行は特殊文字で失敗する。PHPファイルをSCPで転送して `php8.3 /home/mkgrp/bimoni/xxx.php` で実行するのが確実（`/tmp/` はパスが解決できない）
- STGのcrontab編集は `crontab -e`（viが開く）ではなく PHP経由で: `php8.3 -r "file_put_contents('/tmp/nc.txt', '...' . PHP_EOL); passthru('crontab /tmp/nc.txt');"`
- 再応募（cancelled→update）時に古いLineMessageJobが残存する問題 → update前に `status='canceled'` に更新すること
- ライトボックスは純JS実装（`openLightbox(src)` / `closeLightbox()`）、クリックまたはEscで閉じる。回収詳細・報告詳細で使用
- CSVインポートはBOM付きUTF-8（`"\xEF\xBB\xBF"`プレフィックス）でExcel対応
- ¥・カンマ除去は `preg_replace('/[^\d]/', '', $value)` を使う
