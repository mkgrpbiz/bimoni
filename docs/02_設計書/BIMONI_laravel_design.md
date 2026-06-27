# BIMONI Laravel システム設計書

**バージョン:** 2.0  
**最終更新日:** 2026-06-23  
**フレームワーク:** Laravel 11.x  
**PHP:** 8.2以上

> 要件定義 → `BIMONI_requirements.md`
> DB設計 → `BIMONI_db_design.md`
> 画面設計 → `BIMONI_screen_design.md`
> データ移行設計 → `BIMONI_migration_design.md`

---

## 変更履歴

| 日付 | バージョン | 変更内容 |
|------|-----------|---------|
| 2026-06-23 | 1.0 | 初版作成 |
| 2026-06-23 | 2.0 | LINE LIFF・案件種別・応募フロー全体に合わせて全面再設計 |

---

## 1. システム全体アーキテクチャ

```
┌─────────────────────────────────────────────────────────────┐
│                    LINEアプリ（モニター）                      │
│  LINE公式アカウント → LIFF URL → LIFF画面（Blade/SPA）         │
└──────────────────────────┬──────────────────────────────────┘
                           │ HTTPS
┌──────────────────────────▼──────────────────────────────────┐
│                  Laravel アプリケーション                      │
│  ┌─────────────────┐  ┌──────────────────────────────────┐  │
│  │  管理者画面       │  │  モニター向けLIFF画面              │  │
│  │  (Blade / PC)   │  │  (Blade / スマホ)                │  │
│  └────────┬────────┘  └──────────────┬───────────────────┘  │
│           │                          │                        │
│  ┌────────▼──────────────────────────▼───────────────────┐  │
│  │                    Controller / Service                 │  │
│  └────────────────────────────┬──────────────────────────┘  │
│                               │                               │
│  ┌────────────────────────────▼──────────────────────────┐  │
│  │                   MySQL データベース                    │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
         │                              │
         ▼                              ▼
┌────────────────┐            ┌──────────────────┐
│  LINE          │            │  Laravel Storage  │
│  Messaging API │            │  （報告画像・      │
│  （プッシュ通知）│            │    案件画像）      │
└────────────────┘            └──────────────────┘
```

---

## 2. 技術スタック

| 項目 | 採用技術 | 備考 |
|------|---------|------|
| バックエンド | Laravel 11 (PHP 8.2) | |
| フロントエンド | Blade テンプレート + Tailwind CSS | |
| データベース | MySQL 8.0 | |
| 管理者認証 | Laravel Breeze（メール/PW） | |
| モニター認証 | LINE LIFF + LINE Login API | メール/PW認証なし |
| LINE通知 | LINE Messaging API（プッシュ通知） | |
| 画像ストレージ | Laravel Storage（開発: ローカル、本番: S3推奨） | |
| 開発環境 | XAMPP または Laravel Sail（Docker） | |
| メール | Laravel Mail（管理者通知のみ） | モニターへはLINE通知 |

---

## 3. LINE LIFF ログイン設計

### 3.1 認証フロー

```
① LINEアプリ内でLIFF URLを開く
        ↓
② LINE LIFF SDKが自動的にLINEログインを処理
        ↓
③ LIFF SDK から IDトークン（JWT）を取得
        ↓
④ IDトークンをLaravelバックエンドへ送信（POST /liff/auth）
        ↓
⑤ LaravelがLINE APIでIDトークンを検証
   （https://api.line.me/oauth2/v2.1/verify）
        ↓
⑥ 検証成功 → LINE UID（sub）を取得
        ↓
⑦ users テーブルで line_user_id を検索
   ├→ 既存ユーザー: セッション開始 → LIFF画面へ
   └→ 新規ユーザー: プロフィール登録画面へ
```

### 3.2 LIFF認証コントローラー

```php
// app/Http/Controllers/Liff/AuthController.php

public function verify(Request $request)
{
    $idToken = $request->input('id_token');

    // LINE APIでトークン検証
    $response = Http::post('https://api.line.me/oauth2/v2.1/verify', [
        'id_token' => $idToken,
        'client_id' => config('services.line.channel_id'),
    ]);

    if (!$response->successful()) {
        return response()->json(['error' => 'Invalid token'], 401);
    }

    $lineUid = $response->json('sub');
    $displayName = $response->json('name');

    $user = User::firstOrNew(['line_user_id' => $lineUid]);

    if (!$user->exists) {
        $user->line_user_id = $lineUid;
        $user->save();
        // 新規 → プロフィール登録が必要
        return response()->json(['status' => 'new', 'user_id' => $user->id]);
    }

    // 既存 → ログイン
    Auth::guard('liff')->login($user);
    return response()->json(['status' => 'existing', 'user_id' => $user->id]);
}
```

### 3.3 Guard設定（config/auth.php）

```php
'guards' => [
    'web' => [          // 管理者用（メール/PW）
        'driver' => 'session',
        'provider' => 'admins',
    ],
    'liff' => [         // モニター用（LINE LIFF）
        'driver' => 'session',
        'provider' => 'users',
    ],
],
'providers' => [
    'admins' => [
        'driver' => 'eloquent',
        'model' => App\Models\Admin::class,
    ],
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],
```

### 3.4 .env設定

```env
LINE_CHANNEL_ID=xxxxxxxx
LINE_CHANNEL_SECRET=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
LINE_CHANNEL_ACCESS_TOKEN=xxxxxx...
LIFF_ID=1234567890-xxxxxxxx
```

---

## 4. ディレクトリ構成（主要ファイル）

```
bimoni/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── CampaignController.php      # 案件CRUD
│   │   │   │   ├── ApplicationController.php   # 応募者選考
│   │   │   │   ├── ScheduleController.php      # 打診・日程管理
│   │   │   │   ├── UserController.php          # ユーザー管理
│   │   │   │   ├── ReportController.php        # 報告承認
│   │   │   │   ├── PointController.php         # ポイント管理
│   │   │   │   ├── SettlementController.php    # 月末締め
│   │   │   │   ├── LineNotificationController.php
│   │   │   │   └── ImportController.php        # データインポート
│   │   │   └── Liff/
│   │   │       ├── AuthController.php          # LIFFログイン
│   │   │       ├── ProfileController.php       # プロフィール登録
│   │   │       ├── CampaignController.php      # 案件一覧・詳細
│   │   │       ├── ApplicationController.php   # 応募
│   │   │       ├── ReportController.php        # 報告投稿
│   │   │       └── PointController.php         # ポイント確認
│   ├── Models/
│   │   ├── User.php
│   │   ├── Admin.php
│   │   ├── Campaign.php
│   │   ├── Application.php
│   │   ├── ApplicationSchedule.php
│   │   ├── MonitorReport.php
│   │   ├── MonitorReportImage.php
│   │   ├── Point.php
│   │   ├── PointSettlement.php
│   │   ├── PointExchange.php
│   │   └── LineNotification.php
│   ├── Services/
│   │   ├── LineMessagingService.php    # LINE通知送信
│   │   ├── PointService.php            # ポイント付与・計算
│   │   ├── SettlementService.php       # 月末締め処理
│   │   └── ImportService.php           # CSVインポート
│   └── Imports/                        # データ移行用
│       ├── UsersImport.php
│       ├── ApplicationsImport.php
│       └── PointsImport.php
├── resources/views/
│   ├── layouts/
│   │   ├── admin.blade.php             # 管理者レイアウト
│   │   └── liff.blade.php              # LIFF用レイアウト
│   ├── admin/
│   │   ├── dashboard/
│   │   ├── campaigns/
│   │   ├── applications/
│   │   ├── schedules/
│   │   ├── users/
│   │   ├── reports/
│   │   ├── points/
│   │   ├── settlements/
│   │   └── import/
│   └── liff/
│       ├── auth/
│       ├── profile/
│       ├── campaigns/
│       ├── applications/
│       ├── reports/
│       └── points/
├── routes/
│   ├── web.php             # 管理者ルート
│   └── liff.php            # LIFFモニタールート
└── database/
    ├── migrations/
    └── seeders/
```

---

## 5. ルーティング設計

### 管理者ルート（routes/web.php）

```php
// 管理者ログイン
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login']);

    Route::middleware(['auth:web'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('campaigns', CampaignController::class);
        Route::get('campaigns/{campaign}/applications', [ApplicationController::class, 'index'])->name('campaigns.applications');
        Route::patch('applications/{application}/status', [ApplicationController::class, 'updateStatus']);
        Route::get('applications/{application}/schedule', [ScheduleController::class, 'show'])->name('schedules.show');
        Route::post('applications/{application}/schedule', [ScheduleController::class, 'store']);
        Route::patch('schedules/{schedule}/confirm', [ScheduleController::class, 'confirm']);
        Route::get('applications', [ApplicationController::class, 'indexAll'])->name('applications.index');
        Route::resource('users', UserController::class)->only(['index', 'show', 'update']);
        Route::resource('reports', ReportController::class)->only(['index', 'show', 'update']);
        Route::get('points', [PointController::class, 'index'])->name('points.index');
        Route::post('points', [PointController::class, 'store'])->name('points.store');
        Route::resource('settlements', SettlementController::class)->only(['index', 'show', 'update']);
        Route::resource('point-exchanges', PointExchangeController::class)->only(['index', 'update']);
        Route::get('notifications/line', [LineNotificationController::class, 'index'])->name('notifications.line');
        Route::post('notifications/line', [LineNotificationController::class, 'send']);
        Route::get('import', [ImportController::class, 'index'])->name('import.index');
        Route::post('import/users', [ImportController::class, 'importUsers']);
        Route::post('import/applications', [ImportController::class, 'importApplications']);
        Route::post('import/points', [ImportController::class, 'importPoints']);
    });
});
```

### LIFFモニタールート（routes/liff.php）

```php
Route::prefix('liff')->name('liff.')->group(function () {
    Route::post('/auth', [AuthController::class, 'verify'])->name('auth.verify');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/profile', [ProfileController::class, 'store'])->name('profile.store');

    Route::middleware(['auth:liff', 'profile.completed'])->group(function () {
        Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
        Route::post('/campaigns/{campaign}/apply', [ApplicationController::class, 'store'])->name('applications.store');
        Route::get('/mypage', [ApplicationController::class, 'mypage'])->name('mypage');
        Route::get('/reports/{application}/create', [ReportController::class, 'create'])->name('reports.create');
        Route::post('/reports/{application}', [ReportController::class, 'store'])->name('reports.store');
        Route::get('/points', [PointController::class, 'index'])->name('points.index');
    });
});
```

---

## 6. 主要モデルのリレーション

```php
// User.php
public function applications()    { return $this->hasMany(Application::class); }
public function points()          { return $this->hasMany(Point::class); }
public function pointExchanges()  { return $this->hasMany(PointExchange::class); }

// Campaign.php
public function applications()   { return $this->hasMany(Application::class); }
public function category()       { return $this->belongsTo(Category::class); }
public function tags()           { return $this->belongsToMany(Tag::class); }

// Application.php
public function user()           { return $this->belongsTo(User::class); }
public function campaign()       { return $this->belongsTo(Campaign::class); }
public function schedules()      { return $this->hasMany(ApplicationSchedule::class); }
public function report()         { return $this->hasOne(MonitorReport::class); }

// MonitorReport.php
public function images()         { return $this->hasMany(MonitorReportImage::class)->orderBy('sort_order'); }
public function application()    { return $this->belongsTo(Application::class); }
```

---

## 7. LINE Messaging API 通知設計

### 7.1 通知の種類と送信タイミング

| 通知種別 | トリガー | 送信先 |
|---------|---------|--------|
| 応募確認 | モニターが応募 | 応募したモニター |
| 当選通知 | 管理者が当選確定 | 当選したモニター |
| 日程打診 | 管理者が打診日時を登録 | 対象モニター |
| 日程確定 | 管理者が日程確定 | 対象モニター |
| 報告依頼 | 管理者が手動送信 | 対象モニター |
| 協力金付与 | 管理者が承認・付与 | 対象モニター |
| 一括通知 | 管理者が手動送信 | 選択した複数ユーザー |

### 7.2 通知サービス（LineMessagingService）

```php
// app/Services/LineMessagingService.php

public function sendPush(string $lineUserId, string $message): bool
{
    $response = Http::withToken(config('services.line.channel_access_token'))
        ->post('https://api.line.me/v2/bot/message/push', [
            'to' => $lineUserId,
            'messages' => [['type' => 'text', 'text' => $message]],
        ]);

    LineNotification::create([
        'user_id'  => User::where('line_user_id', $lineUserId)->value('id'),
        'message'  => $message,
        'status'   => $response->successful() ? 'sent' : 'failed',
    ]);

    return $response->successful();
}
```

---

## 8. 月末締め・協力金付与の設計

### 8.1 処理フロー

```
毎月末（手動実行）
    ↓
SettlementController::close()
    ↓
当月の承認済み（approved）応募を取得
    ↓
各ユーザーのポイントを集計
    ↓
point_settlements に締め記録を作成
    ↓
points に settlement_id を紐付け
    ↓
applications.status を point_granted に更新
    ↓
翌月10日：支払完了後に settlement.status を paid に更新
```

### 8.2 ポイント付与サービス（PointService）

```php
// app/Services/PointService.php

public function grantForReport(MonitorReport $report): void
{
    $application = $report->application;
    $campaign = $application->campaign;
    $user = $application->user;

    DB::transaction(function () use ($application, $campaign, $user) {
        Point::create([
            'user_id'        => $user->id,
            'type'           => 'earn',
            'amount'         => $campaign->cooperation_fee,
            'reason'         => "案件「{$campaign->title}」モニター協力金",
            'application_id' => $application->id,
        ]);

        $user->increment('point_balance', $campaign->cooperation_fee);
        $application->update(['status' => 'approved', 'approved_at' => now()]);
    });
}
```

---

## 9. 実装ロードマップ（Phase別）

### Phase 1: LIFFログイン・会員基盤（最優先）

| # | タスク |
|---|--------|
| 1 | Laravelプロジェクト作成・DB接続設定 |
| 2 | マイグレーション作成（users, admins） |
| 3 | 管理者ログイン実装（Laravel Breeze） |
| 4 | LINEデベロッパーコンソールでLIFFアプリ作成 |
| 5 | LIFF認証コントローラー実装 |
| 6 | モニタープロフィール登録画面実装 |
| 7 | ユーザー管理画面（A-08, A-09）実装 |

### Phase 2: 案件管理

| # | タスク |
|---|--------|
| 8 | マイグレーション作成（campaigns, categories, tags） |
| 9 | 管理者：案件CRUD画面（A-02, A-03, A-04）実装 |
| 10 | モニター：案件一覧・詳細（M-03, M-04）実装 |

### Phase 3: 応募管理・打診管理

| # | タスク |
|---|--------|
| 11 | マイグレーション作成（applications, application_schedules） |
| 12 | モニター：応募機能実装 |
| 13 | 管理者：応募者選考画面（A-06）実装 |
| 14 | 管理者：打診・日程管理画面（A-07）実装 |
| 15 | ダッシュボード（A-01）実装 |
| 16 | LINE当選通知・打診通知の実装 |

### Phase 4: モニター報告管理

| # | タスク |
|---|--------|
| 17 | マイグレーション作成（monitor_reports, monitor_report_images） |
| 18 | モニター：報告投稿画面（M-06）実装 |
| 19 | 管理者：報告一覧・承認画面（A-10, A-11）実装 |

### Phase 5: 協力金・ポイント管理

| # | タスク |
|---|--------|
| 20 | マイグレーション作成（points, point_settlements, point_exchanges） |
| 21 | PointServiceの実装（付与・調整） |
| 22 | SettlementServiceの実装（月末締め） |
| 23 | 管理者：ポイント管理・月末締め画面（A-12, A-13）実装 |
| 24 | モニター：協力金確認画面（M-07）実装 |

### Phase 6: LINE通知連携

| # | タスク |
|---|--------|
| 25 | LineMessagingServiceの実装 |
| 26 | 各通知トリガーへの組み込み |
| 27 | 管理者：LINE一括通知画面（A-15）実装 |

### Phase 7: 既存データ移行

| # | タスク |
|---|--------|
| 28 | CSVインポート設計・バリデーション実装 |
| 29 | ユーザーインポート（ImportController + ImportService） |
| 30 | 応募履歴インポート |
| 31 | ポイント履歴インポート |
| 32 | エルメ回答者ID紐付け・重複統合ガイド実装 |

---

## 10. 環境構築コマンド（初回）

```bash
# Laravelプロジェクト作成
composer create-project laravel/laravel bimoni

# Breezeインストール（管理者認証用）
composer require laravel/breeze --dev
php artisan breeze:install blade

# HTTP通信ライブラリ（LINE API呼び出し用）
composer require guzzlehttp/guzzle

# npmパッケージインストール・ビルド
npm install && npm run dev

# .envを編集してDB接続・LINE設定を追加後
php artisan migrate

# 開発サーバー起動
php artisan serve
```

---

## 11. ドキュメント一覧

| ドキュメント | ファイル名 |
|------------|-----------|
| 要件定義書 | BIMONI_requirements.md |
| 画面設計書 | BIMONI_screen_design.md |
| DB設計書 | BIMONI_db_design.md |
| Laravel設計書（本ファイル） | BIMONI_laravel_design.md |
| データ移行設計書 | BIMONI_migration_design.md |
