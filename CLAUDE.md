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

### デプロイ手順（STG）
自動デプロイは廃止（GitHub Actionsのワークフローは削除済み）。mainにpushしたあと、毎回SSHで手動デプロイが必要：
```bash
cd /home/mkgrp/bimoni
git pull
php8.3 artisan migrate --force
php8.3 artisan view:clear
php8.3 artisan route:clear
```
> STGのURL: `https://stg.bimoni.online` / DB: `mkgrp_bimonistg` / LINE公式: `@204zmull`
> STGのcronは `schedule:run` を毎分実行。`proposals:auto-cancel`（5分ごと）と `line:send-messages`（毎分）が動く。

### デプロイ手順（本番）
```bash
cd /home/mkgrp/bimoni_prod
git pull
php8.3 artisan migrate --force
php8.3 artisan view:clear
php8.3 artisan route:clear
```
> 本番URL: `https://app.bimoni.online` / DB: `mkgrp_bimoni` / LINE公式: `@367styyv`
> 本番cronも同様に `schedule:run` を毎分実行。

> **注意**: サーバーのデフォルトPHPは8.0。必ず `php8.3` を使う。
> **STGと本番のLINEチャンネル・トークンは絶対に混ぜない**（チャンネルが別々）

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
- `imported_from`: `'spreadsheet'`（CSVインポート）/ `'new'`（LINE通常登録）
- `transfer_registered_at`: 引き継ぎ登録フロー（`member.transfer`）を経由して登録した日時
- `new_register_confirmed_at`: 通常新規登録ユーザーを管理画面で「確定済み」にした日時

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
- `cooperation_fee`: 自動計算（`calcFee($itemCount, $shippingFee)`: **5個以上は 800円×商品数＋送料**、4個以下は 800円×商品数のみ（送料なし））
- `tracking_number`: 追跡番号（重複スキップキー）
- `box_image` / `label_image`: 添付画像パス（null許容）
- `estimated_arrival_date`: 到着予定日（null許容、`?->format()` でnull安全に）
- `adjustment_amount`: 金額修正。**実際の合計金額は必ず `totalFee()`（`cooperation_fee + adjustment_amount`）経由で参照する**（協力金管理の集計・CSV/全銀エクスポート・ダッシュボードKPI・会員マイページの支払予定額・回収管理一覧など）。過去に`cooperation_fee`を直接`sum()`していた箇所が複数あり、金額修正が一切反映されない不具合があった

### MonitorReport
- `purchase_type`: ENUM('initial', 'continuation', 'other')
- `bonus_amount`: nullable unsignedInteger。キャンペーン列の金額（インポート時）またはapplication.bonus_amountからコピー（会員報告時）
- `purchase_amount`: 実際にかかったモニター経費
- 協力金の表示・計算: `purchase_amount + (cooperation_fee or continuation_cooperation_fee) + bonus_amount`
- **継続報告（`purchase_type='continuation'`）の協力金は必ず `campaign.continuation_cooperation_fee` を使う**（`cooperation_fee` を使うと初回金額になるバグ）

### Campaign
- `collection_requirement`: ENUM('回収必須', '回収不要') nullable（旧称「回収前提」。2026-07-14に「前提」という表現が分かりにくいため「必須」に改称、マイグレーションで既存データも一括変換済み）
  - 条件チェックは必ず `=== '回収必須'`（truthy判定だと'回収不要'でも引っかかる）
  - 回収必須の場合、応募フォームに警告メッセージ表示
- `collection_info`: DBカラムは残存しているがフォーム・会員ページからは削除済み（未使用）
- LINE自動送信設定（`monitor_invite_message` / `monitor_end_message`）は案件ごとに設定。新規案件は既存案件を複製して作成する想定（デフォルト機能は廃止）
- `continuation_condition`: ENUM('2回前提', '3回前提') nullable。継続前提の商品用。設定すると会員応募フォームの継続希望確認欄を非表示にし、応募時点で`continuation_wish='希望'` + `continuation_response='possible'` + `continuation_responded_at=now()`を自動セット（バッジ表示は「OK」になる。`continuation_response`が`continuation_wish`より優先されるため）

### コース指定設定（`course_settings_enabled` / `CampaignCourse` / `Application.course_id`）
1商品に複数の購入コース（単発○本、継続○回など）があり、コースによって初回/継続購入費や案内文が異なる案件向けの機能。

- `Campaign.course_settings_enabled`: 有効化フラグ。有にすると案件編集フォームに「コース指定設定」ブロックが出る
- `Campaign.course_normal_name` / `course_normal_percentage`: 「通常コース」（詳細情報の初回購入費・継続購入費等の既定値を使うケース）の呼称と発生比率。**コース未指定（`Application.course_id === null`）の表示・集計は全てこの名前を使う**（固定文字列「通常コース」をハードコードしない）
- `CampaignCourse`（`campaign_courses`テーブル）: `name`, `initial_purchase_fee`, `course_type`（ENUM '単発'/'継続'）, `continuation_count`（2 or 3、継続のみ）, `continuation_fee_2` / `continuation_fee_3`（継続のみ）, `percentage`, `invite_message`, `sort_order`
  - `cost()`: 単発=`initial_purchase_fee`のみ、継続=`initial_purchase_fee + continuation_fee_2`（3回の場合はさらに`+continuation_fee_3`）
  - 案件更新のたびに**全削除→送信内容で作り直す**（delete-and-recreate、`CampaignController::syncCourses()`）。個別のid付きupdateはしない
- **モニターコスト自動計算**（`Campaign::calculatedMonitorCost()`）: コース設定が有の場合、`通常コストの加重平均（×course_normal_percentage）+ Σ各コースのcost()×percentage + 協力金 + 紹介単価`。JS側（`_form.blade.php`の`calcMonitorCost()`）にも同じ式を実装しており、**両方を必ず同期させること**
- **粗利（`gross_profit`）はJSのリアルタイム計算に依存すると保存タイミング次第で古い値のまま保存されるバグがあった** → `CampaignController::store()/update()`で`syncCourses()`実行後にサーバー側で`calculatedMonitorCost()`を使って再計算し、クライアント送信値を上書きする（`recalculateGrossProfit()`）
- **コース専用プレースホルダーコード**（`invite_message`内で使用可）: `{{コース名N}}` `{{初回購入費N}}` `{{継続購入費N-2}}` `{{継続購入費N-3}}`（N=そのコースの並び順+2、コースごとに番号が変わるため他コースの値と混同しない）。`CampaignCourse::resolveTemplate()`で解決してから`Campaign::resolveTemplate()`に委譲。案件共通コード（`{{商品名}}`等、下記参照）に加え`{{コース名}}`（無番号）は案件共通メッセージ内で`course_normal_name`に解決される
- **打診時のコース選択**: `Application.course_id`（nullable, `campaign_courses`への外部キー、`nullOnDelete`）。打診モーダルは3箇所に存在し**すべてに同期が必要**: ①`admin/campaigns/{id}/applications`の案件別ページ、②`admin/applications`の全案件横断ページ（案件が混在するため打診ボタンに`courses`をJSON埋め込みしJSで動的に選択肢構築）、③`admin/applications/{id}`詳細ページの「ステータス変更」フォーム（※コース選択はここではなく別枠の「コースの編集」フォームのみに置く。重複させない）
  - `ApplicationController::updateStatus()`で`$request->has('course_id')`ならステータスに関わらず保存する（`line_contacted`遷移時限定にすると再打診や直接完了操作でコースが保存されないバグになる）
  - 打診確定後の案内文（`ProposalController::createMonitorGuideJob()`）は`$application->course_id`があれば`$course->invite_message`（空ならcampaign既定にフォールバック）を使用。モニター終了案内文（`monitor_end_message`）はコースに関わらず常に案件共通
  - コースが割り当てられた応募には**継続LINE送信ボタン（継続打診）を出さない**（`campaign_index.blade.php`のボタン条件に`&& !$app->course_id`）。コースの単発/継続は応募時点の`continuation_wish`とは別概念のため
- **継続率指標はコース未指定（`course_id === null`）の応募のみで集計する**（`ApplicationController::index()`の「未達成目標継続率」アラート、`campaignIndex()`の継続率サマリー、いずれも`whereNull('course_id')`）。コースの「継続」タイプは商品として継続が確定しており確率的な継続確認の対象ではないため
- 応募管理一覧・案件別ページ・応募詳細に「コース」列/欄を表示。テーブルは`overflow-x-auto`で横スクロールするため列追加で窮屈にはならない

---

## LINE LIFF

### 招待ページ（`resources/views/invite.blade.php`）
- 「LINEで登録する」ボタンは `line://app/{LIFF_ID}?referral_code={code}` で直接LINEアプリを開く
- 招待コードをURLパラメータで渡すので紹介元の追跡が継続される
- モーダルは廃止（`line://browser?url=` は動かなかった）
- `liff.login()` に `botPrompt` は設定しない（友だち追加はメンバーページのモーダルで行う）

### LINE友だち未追加モーダル（`resources/views/layouts/member.blade.php`）
- ログイン済みユーザーに対し `liff.getFriendship()` で友だち追加状態を確認
- 未追加・ブロック中（どちらも `friendFlag: false`）の場合はモーダルを表示
- 誘導先は `config('services.line.official_account_id')`（環境変数 `LINE_OFFICIAL_ACCOUNT_ID`）で切り替え
  - 本番: `@367styyv` / STG: `@204zmull`
- **登録フォームページ（`member.register*`）と引継ぎページ（`member.transfer*`）は除外**
- **必須設定**: LINEデベロッパーコンソールのLIFFアプリで「Add friend option」をONにして公式アカウントを紐付けること（これがないと全員に表示される）
- LIFFチャンネルはエルメのLINEデベロッパーコンソール（チャンネルID: 2007390214）で管理

### LINE自動送信（`monitor_invite_message` / `monitor_end_message`）
- 使用できるコード: `{{商品名}}` `{{初回購入費}}` `{{モニター協力金}}` `{{解約について}}` `{{モニター案内文}}` `{{リンク}}` `{{案内日時}}` `{{コース名}}`（通常コースのコース名、コース設定が有の場合のみ意味を持つ）
- `{{案内日時}}`: `invited_at->format('n月j日 H:i')` + `〜invited_end_at->format('H:i')` の形式で置換（ProposalController::createMonitorGuideJob）
- コース別の案内文専用コード（`{{コース名N}}`等）は上記とは別体系。「コース指定設定」の項を参照

---

## ポータル（代理店ポータル）

### 共通フィルター（ユーザー管理・報告管理・報酬管理）
- 累計 / 月次 トグル（**デフォルトは月次**）
- 子代理店フィルター（親代理店のみ表示）
- コード別プルダウン（コードが複数ある場合）
- ユーザー一覧は `profile_completed_at` not null のみ表示（LIFF登録だけのユーザーを除外）
- **継続報告（purchase_type='continuation'）は表示・集計しない**（紹介報酬対象外）

### ページ構成
| ページ | 内容 |
|--------|------|
| ユーザー管理 | 登録者一覧・集計（登録/応募/報告数） |
| 報告管理 | 承認済み報告一覧（初回報告のみ） |
| 報酬管理 | 案件別報酬集計 |
| 子代理店管理 | 子代理店一覧・コード管理（親のみ） |

### 子代理店作成
- 代理店名・報酬設定・招待コード（任意/空欄=自動生成）
- コード追加・削除（登録者がいない場合のみ削除可）

---

## 管理画面

### ダッシュボード（`admin/dashboard`）
- 月次/累計の`$metrics`とは別に、常に「今日・昨日」基準の**日次KPI（本日の状況）**を表示（`DashboardController::calcDailyKpi()`）
  - 応募数（本日/昨日）: `applied_at`の日付で単純カウント
  - 実施完了数（本日/昨日）: `completed_at`の日付でカウント。ステータスは`completed`だけでなく`reported`/`approved`/`point_granted`も含める（実施完了後さらに進んでいても実施完了扱い、月次`$metrics`の実施数ロジックと同じ考え方）
  - 打診中・予約中・実施確認中: 日次比較ではなく**現在のパイプライン件数**のスナップショット（`status`別の単純カウント）
  - 昨日件数をカードに直接併記しているため、増減バッジ（`diffBadge()`）は表示しない（他の月次指標カードのみバッジ付き）
- 打診アラート（`buildAlerts()`の重複ブッキング・翌日未達成）は**`campaign.status='published'`のみ対象**（`whereHas('campaign', ...)`）。終了済み案件でも`CampaignDailySlot`の目標件数が残っていると、実際には誰も応募しないため常に「未達成」として誤カウントされるバグがあったため、公開中の案件だけに絞るよう修正済み（`admin/daily-slots`一覧ページはデフォルトで`status=published`フィルタがかかっているため、ダッシュボード側が終了案件を含めていると件数が食い違って見える）
- グラフ「売上・協力金推移」（`getChartData()`）の協力金は、以前は`CampaignApprovalReflection.reflection_count × campaign.cooperation_fee`という**単価だけの概算**で、実際のモニター経費（`purchase_amount`）・継続報告の`continuation_cooperation_fee`・ボーナス・回収報告分が一切含まれておらず、メインKPIカードの「協力金」と桁違いに乖離するバグがあった → `MonitorReport`（`created_at`の年月で集計、承認済みのみ）＋`CollectionReport`を使い、メインKPIカードの`cooperationFee`と同じ計算式に統一

### 応募管理（案件別一覧）
- 並び順: 実施完了/報告済/承認済/付与済/キャンセル以外のステータスは応募日時の古い順、それ以外（実施完了・キャンセル等の完了系グループ）は**案内日時（`invited_at`）の新しい順**（`ApplicationController::campaignIndex()` の `orderByRaw`）
- 実施完了サマリーバーに「応募数（総数/残件数）」を表示。残件数はステータスが「応募」（pending）のまま未処理の件数
- 詳細ページ（`admin/applications/{application}`）
  - 「応募情報」: ユーザー名 / 案件名 / 応募日時 / 案内日時 / ステータス / 継続ステータス（希望/不可/確認中/OK/NG、`admin/users/show.blade.php`と同じ継続バッジロジック）/ コース（コース設定が有の案件のみ）
  - 「モニター情報」サイドバー: 氏名/フリガナ/性別/生年月日のみ（エリア以下は削除済み）＋「ユーザー詳細」ボタン（`admin.users.show`）。継続希望/回答の編集は上部の「継続情報の編集」フォームで行う。コースの編集は別枠の「コースの編集」フォーム（ステータス変更フォームには置かない、重複させない）

### 代理店管理
- 一覧: 代理店名・子代理店数・コード数・登録数・応募数・報告数・詳細/削除
- 削除: 登録者がいない代理店のみ可（子代理店・コードも一括削除）

### 紹介報酬管理
- 月次の報酬一覧・承認/支払い処理
- 詳細: 代理店のコード別登録者・承認済み報告を表示
- **初回報告のみ対象**（`purchase_type='initial'`）。継続・回収は紹介報酬なし
- 全否認案件（`CampaignApprovalReflection.is_all_denied=true`）の金額は期待報酬から除外

### ユーザー管理
- 登録コード（`referred_by_code`）を表示
- 詳細ページ（`admin/users/{user}`）: 応募履歴・モニター報告履歴・回収報告履歴を`<details>`アコーディオンで表示（応募履歴が一番上でopen、他は閉じた状態）
  - 応募履歴: 応募日時 / 案件名 / ステータス / 案内日時（`invited_at`） / 継続（`continuation_response`/`continuation_sent_at`/`continuation_wish`から判定するOK/NG/確認中/希望/不可バッジ、`campaign_index.blade.php`と同じロジック） / 詳細（`admin.applications.show`）
  - モニター報告履歴（旧名: モニター実施履歴）: 報告日時 / 案件名 / 報告ステータス / 支払いステータス / 支払い金額 / 支払日 / 詳細（`admin.reports.show`）
  - 回収報告履歴: 報告日時 / 案件名（`$cr->campaigns()`で複数案件をカンマ区切り表示） / 商品数 / 報告ステータス / 支払いステータス / 支払日 / 詳細（`admin.collection_reports.show`）
  - `CollectionReport`モデルの`paid_at`は元々`casts()`に含まれておらず文字列のままだった（`->format()`でエラー）→ `datetime`キャストを追加して修正
  - `User::collectionReports()`リレーションを新規追加

### 報告管理（`admin/reports`）
- 一覧列: 報告日時 / ユーザーID(`bimoni_user_id`) / 登録コード / LINE表示名 / 名前 / フリガナ / 案件名 / モニター協力金 / ステータス / 詳細
- 詳細: 画像クリックでライトボックス拡大、承認・差戻しアクションあり
- 詳細サイドバー「応募情報」: ユーザー名 / 案件名 / 応募日時（`application.applied_at`） / 実施日時（`application.completed_at`） / 報告日時（`report.created_at`） / 継続ステータス（希望/不可/確認中/OK/NG、`admin/users/show.blade.php`と同じ継続バッジロジック）
- 「応募詳細」「ユーザー詳細」ボタンを並べて表示（`admin.applications.show` / `admin.users.show`）

### 回収管理（`admin/collection_reports`）
- 一覧列: 報告日時 / ユーザーID(`bimoni_user_id`) / 登録コード / LINE表示名 / 名前 / フリガナ / 商品数 / 到着予定日 / 追跡番号 / ステータス / 詳細
- 詳細サイドバー「ユーザー情報」: ユーザーID/LINE表示名/名前/フリガナのみ（エリアは削除済み）＋「ユーザー詳細」ボタン（`admin.users.show`）
- 詳細: 段ボール画像・発送伝票画像をライトボックスで拡大、承認・差戻しアクションあり

### 紹介報酬詳細
- 承認報告があるユーザーのみ表示（`$activeUsers`）
- 戻るボタンのルートは `['year' => $month->year, 'month' => $month->month]`（`'month' => 'Y-m'` 形式はNG→500エラー）
- 期待報酬0円の場合は「処理不要」バッジ表示
- 承認ユーザー一覧CSVダウンロード機能あり（`referrals.csv` ルート）

### 協力金管理（`admin/points`）
- 先月・当月ブロックで予約済み・支払済み処理
- ダッシュボードのリンクは `['year' => $prevMonth->year, 'month' => $prevMonth->month]` 形式で渡す（`'Y-m'` 形式はNG→500エラー）
- `markReserved` / `markPaid` の `whereBetween` は必ず `copy()` を使う（同一Carbonオブジェクトに連続で呼ぶと両方が月末になるバグあり）

### LINE紐付け管理（`admin/line-links`）
- **未完了（インポートデータ）**: `imported_from='spreadsheet'` かつ `line_user_id` が null または `IMPORT_` 始まり。新しい順にソート
- **紐付け登録（新規登録データ）**: `transfer_registered_at` not null かつ未紐付け（スプレッドシートユーザーとして実LINE IDが紐いた自動マッチ済みは除外）
- **新規登録（通常登録データ）**: `imported_from='new'` かつ `profile_completed_at` not null かつ `transfer_registered_at` null かつ `new_register_confirmed_at` null
- **完了**: `imported_from='spreadsheet'` かつ実LINE ID保持
- 「紐付け」ボタン: `link()` → liffUserのデータをimportUserに移行しliffUserを削除。`DB::transaction()` 内で先にliffUserを delete してからimportUserを update（unique制約対策）
- 「新規として確定」: `new_register_confirmed_at = now()` をセット → 新規登録タブから消える

### インポート機能
- **ユーザーインポート**: erme_respondent_idが既存ユーザーと一致→上書き更新（スキップしない）。メールのみ一致→スキップ
- **応募リストインポート**（2026-07-11に仕様変更・全面書き換え）: 案件を1つ選ぶ方式は廃止。CSV自体に複数案件が混在していてもそのまま投入できる
  - `ImportService::skipToApplicationHeader()` でサマリー行（集計情報）を自動スキップし「回答者ID」を含む行をヘッダーとして使用
  - `normalizeEncoding()` を `parseCsv()` / `skipToApplicationHeader()` の先頭で実行し、Shift-JIS/UTF-8を自動判定して変換（貼り付け元によって文字コードが揺れるため）
  - `parseCsv()` で重複ヘッダーは `_2` `_3` でリネーム（最初の列を優先）
  - `normalizeApplicationRows()` で `ステータス共有` 列は明示的にスキップ（列名がそのままでも読み込み対象外）
  - 案件は**行ごとに「案件名」列から** `Campaign::where('title', $campaignName)->first()` で自動特定（`$campaignCache` でキャッシュ）。見つからない/空欄の行はエラーとして報告しスキップ
  - 一致判定キーは **案件名 × 応募日時 × 回答者ID**（`(user_id, campaign_id, applied_at)`）
  - 更新・作成・スキップのルール（もともとのCSV運用で実施完了の反映漏れが400件以上あった問題への対応）:
    - 一致する既存レコードがあり、CSV側のステータスが `completed`（実施完了）または `cancelled`（キャンセル）→ 更新
    - 一致する既存レコードがあり、CSV側のステータスがそれ以外 → スキップ（何もしない）
    - 一致する既存レコードがない → 新規作成（ステータス問わず）
    - **保護対象ステータス**（既存レコードが以下の場合は絶対に上書きしない。CSV側の内容に関わらず常にスキップ）: `reported`（報告済） / `approved`（承認済） / `point_granted`（付与済） / `scheduled`（予約中） / `line_contacted`（打診中）。予約中・打診中は別工程（打診・日程調整フロー）で管理されているためユーザー確認済みで保護対象に追加
  - ステータスマッピング: 実施完了→completed / 実施確認中→confirming / キャンセル→cancelled / 予約中→scheduled / 打診中→line_contacted / 空欄→pending
  - `invited_at`: 採用日+採用時間 または 案内日+案内時間 から設定
  - `available_times`: 「実施可能時間」列から設定（いつでもOK含む）。既存ユーザーも更新
  - `continuation_flag`（継続打診）: 完全一致の `TRUE` ではなく `str_contains($flagRaw, 'OK')` で判定（実データが「継続OK」等の文字列だったため）
  - `wants_continuation`（継続希望）: 「希望」or「不可」
  - ヘッダー名は実データに合わせて `応募日時` `名前` `実施可能時間` `継続希望` `継続打診` `案件名` に対応（旧ヘッダー名も後方互換で残している）
  - applications テーブルの `(user_id, campaign_id)` unique制約は削除済み（同一ユーザーが複数回応募可）
  - 案件別インポートデータ削除スクリプト: `php8.3 fix_delete_campaign_applications.php {campaign_id}`
- **報告インポート**: 列 = 回答者ID, 回答者名（任意）, 名前, フリガナ, 案件名, 初回か継続, モニター経費, キャンペーン
  - ステータスは常に `approved`
  - キャンペーン列の値を `bonus_amount` として保存（空欄は null）
  - 重複チェック: ユーザー×案件×報告日時の**完全一致**（日付のみでなく時間まで一致した場合のみスキップ）
  - `purchase_type`: `=== '継続'` の完全一致で `continuation`、それ以外は `initial`
  - `purchase_amount` = モニター経費（¥・カンマ除去）
  - ユーザーが見つからない場合は `line_user_id='IMPORT_xxx'` で新規作成
  - `cooperation_fee` 列はMonitorReportに存在しない（Campaignから取得するため保存しない）
  - 応募管理とは完全に切り離し（application_id は既存応募があれば紐付け、なければ null）
- **回収インポート**: 列 = 回答者ID, 回答者名, 名前, フリガナ, 商品数, 送料, 追跡番号
  - 重複チェック: ユーザー×報告日時（同日同ユーザーはスキップ）
  - 協力金は `CollectionReport::calcFee()` で自動計算（5個以上は 800円×商品数＋送料、4個以下は 800円×商品数のみ）
  - ステータスは `approved`

---

## 報酬計算ロジック（PortalService::calcReward）
- **親代理店**: `campaign.referral_fee` をそのまま受け取る
- **子代理店**: `child_reward_{fee}` を受け取る。この値は**子自身のAgentレコード**に保存されている（親のレコードではない）
  - 過去バグ: `$agent->parent?->childRewardFor($fee)` と親側を参照していたため子の設定が反映されず常に0になっていた → `$agent->childRewardFor($fee)` に修正済み
- 報酬管理画面（ポータル）の表示は「入り」と「出」で統一：**全体紹介報酬**（案件の紹介報酬合計）/ **子支払総額**（子代理店への支払い合計）。親の取り分（利益・差額）という表現はやめた
  - 「差額」は**親が子を見ている場合のみ**表示（子代理店が自分自身を閲覧している時は非表示。子に親の利益率を見せない）
- 報告管理画面（ポータル）は閲覧者自身の取り分（`calcReward()`の結果）を表示する。案件の`referral_fee`そのままを出さない
- 報酬管理に単価別（fee別）サマリーを追加。全否認（承認0件・却下のみ）の案件も一覧に出るよう、`approvedReports()` に加えて `rejectedReports()` から案件IDを集めて集計対象にしている（承認済みだけでフィルタすると却下のみの案件が集計から漏れて全否認が常に0件になるバグがあった）

---

## 会員マイページ（`member/mypage`）

### 「応募中」タブの募集終了案件アコーディオン
- 「応募中」ステータス（pending/selected/line_contacted/scheduled/confirming）の応募のうち、案件が既に募集終了（`campaign.status === 'closed'`）のものは、タブ内で削除せず下部に折りたたんで表示する（`MypageController::index()` の `$applyingActive` / `$applyingEnded`）
- タブのバッジ件数は分割前の全件数（`$groups['応募中']`）のまま変えない
- 折りたたみは `<details>/<summary>`（ネイティブHTML、JS不要）。「応募中」タブのみ対象で、実施完了・キャンセル等の他タブは影響なし

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
- **`line_contacted` 以外のステータスはすべて expired ページ**（回答済みのリンクを再度開いても変更・キャンセル不可）
- 「いいえ」→別日程選択にも制限なし（管理画面側での打診送信時のみロックチェック）
- `declineNo` にもステータスチェックあり（`line_contacted` 以外は expired ページ）

---

## 注意事項・過去のミス

- LIFF WebView（iOS WKWebView）はCSRFセッションが引き継がれないため、`member/auth/liff-callback` と `member/register` をCSRF除外している（`bootstrap/app.php` の `validateCsrfTokens(except: [...])` で設定）
- **PHPはURLクエリパラメータ名のドットをアンダースコアに変換する**（例: `?liff.state=xxx` → `$request->get('liff_state')`）。`$request->get('liff.state')` は常にnullを返すので注意
- 引き継ぎ登録リンクは `https://liff.line.me/{LIFF_ID}?from=transfer`。サーバー側で `liff_state` を `parse_str` でデコードして `$from` を取得し、PHP変数としてBladeに渡す（JSのURL読み取りに依存しない）
- LINEチャンネルアクセストークンはbase64文字列で大文字・小文字が区別される。.envに貼るときはコピーミスに注意（`O`と`o`など）。貼った後に401エラーが出たらまず文字を目視確認
- フリガナ入力はIME変換中に `oninput` が発火するバグがある → `compositionstart`/`compositionend` で変換中フラグを管理し、変換確定後に `hiraToKata()` を呼ぶ（`layouts/member.blade.php` でグローバル処理済み）
- `alert()` を Promise の `.then()/.catch()` 内で呼ぶとブラウザにブロックされる → `document.execCommand('copy')` で同期コピー後に `alert()` を呼ぶ
- コピーボタンは必ず同期処理 + `alert('コピーしました')` のセットで実装
- SSHの秘密鍵は `C:\Users\user\.ssh\xserver.key`
- STGのDBをtinker経由で操作するときは、PowerShellからの直接実行は特殊文字で失敗する。PHPファイルをSCPで転送して `php8.3 /home/mkgrp/bimoni/xxx.php` で実行するのが確実（`/tmp/` はパスが解決できない）
- STGのcrontab編集は `crontab -e`（viが開く）ではなく PHP経由で: `php8.3 -r "file_put_contents('/tmp/nc.txt', '...' . PHP_EOL); passthru('crontab /tmp/nc.txt');"`
- 再応募（cancelled→update）時に古いLineMessageJobが残存する問題 → update前に `status='canceled'` に更新すること
- ライトボックスは純JS実装（`openLightbox(src)` / `closeLightbox()`）、クリックまたはEscで閉じる。回収詳細・報告詳細で使用
- CSVインポートはBOM付きUTF-8（`"\xEF\xBB\xBF"`プレフィックス）でExcel対応
- ¥・カンマ除去は `preg_replace('/[^\d]/', '', $value)` を使う
- Carbon の `whereBetween` で同一オブジェクトに `startOfMonth()` → `endOfMonth()` と連続して呼ぶと両方が月末になるバグ → 必ず `copy()` を使う
- MonitorReport を全削除すると Application の status が `reported` のまま残る。再報告は可能だがマイページ表示がおかしくなるので application の status も `completed` に戻す必要がある
- MySQLの `SUM(条件式)`（例: `SUM(continuation_response = "possible")`）は、集計対象の全行で条件式がNULL評価される場合（比較対象カラム自体がNULLなど）に**SUM結果もNULLを返す**。PHP側で`int`型引数に渡すとTypeErrorで500になる（本番で実際に発生）→ `COALESCE(SUM(...), 0)` で必ず0にフォールバックする
- 全案件横断ページと案件別ページのように**同じ機能（打診モーダル等）が複数のBladeファイルに重複実装されている箇所がある**。片方だけ直して満足せず、`grep`で同名の関数・同じルートへのフォーム送信がないか横断確認すること（コース選択欄の実装漏れで一箇所だけ機能しない事故が起きた）
- 動的な値をJSに埋め込むとき、Bladeの`{{ }}`は生の`{{`/`}}`文字を含む文字列（プレースホルダーコード等）と衝突してコンパイルエラーになる。`@json()`（`<script>`タグ内でJS変数に代入する形）か、複数箇所で使うなら`String.fromCharCode(123)`等で中括弧をJS側组み立てにする
