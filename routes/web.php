<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\ContinuationController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\Admin\ApprovalReflectionController;
use App\Http\Controllers\Admin\CampaignBonusController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CampaignDailySlotController;
use App\Http\Controllers\Admin\CollectionReportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FormFieldController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\LineNotificationController;
use App\Http\Controllers\Member\AuthController as MemberAuth;
use App\Http\Controllers\Member\CampaignController as MemberCampaign;
use App\Http\Controllers\Member\MypageController as MemberMypage;
use App\Http\Controllers\Member\RegisterController as MemberRegister;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\PointController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Portal\AuthController as PortalAuth;
use App\Http\Controllers\Portal\UserController as PortalUser;
use App\Http\Controllers\Portal\ReportController as PortalReport;
use App\Http\Controllers\Portal\RewardController as PortalReward;
use App\Http\Controllers\Portal\ChildController as PortalChild;
use App\Http\Controllers\Portal\SettingsController as PortalSettings;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SettlementController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// 継続依頼応答ページ（認証不要・トークンで保護）
Route::prefix('continuation/{token}')->name('continuation.')->group(function () {
    Route::get('/',        [ContinuationController::class, 'confirm'])->name('confirm');
    Route::post('/accept', [ContinuationController::class, 'accept'])->name('accept');
    Route::post('/decline',[ContinuationController::class, 'decline'])->name('decline');
});

// 打診ページ（認証不要・トークンで保護）
Route::prefix('proposals/{token}')->name('proposals.')->group(function () {
    Route::get('/',        [ProposalController::class, 'confirm'])->name('confirm');
    Route::post('/yes',    [ProposalController::class, 'acceptYes'])->name('yes');
    Route::get('/no',      [ProposalController::class, 'declineNo'])->name('no');
    Route::post('/slot',   [ProposalController::class, 'selectSlot'])->name('slot');
    Route::post('/cancel', [ProposalController::class, 'cancel'])->name('cancel');
    Route::get('/complete',[ProposalController::class, 'complete'])->name('complete');
    Route::post('/revert', [ProposalController::class, 'revert'])->name('revert');
});

Route::prefix('admin')->name('admin.')->group(function () {
    require __DIR__.'/auth.php';

    Route::middleware('auth:web')->group(function () {
        Route::get('/', fn() => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('campaigns', CampaignController::class);
        Route::post('campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicate'])->name('campaigns.duplicate');
        Route::post('campaigns/reorder', [CampaignController::class, 'reorder'])->name('campaigns.reorder');
        Route::patch('campaigns/{campaign}/toggle-visible', [CampaignController::class, 'toggleVisible'])->name('campaigns.toggle_visible');
        // 承認反映管理
        Route::get('approval-reflections', [ApprovalReflectionController::class, 'index'])->name('approval_reflections.index');
        Route::patch('approval-reflections/{campaign}', [ApprovalReflectionController::class, 'update'])->name('approval_reflections.update');
        Route::patch('approval-reflections/{campaign}/toggle-denied', [ApprovalReflectionController::class, 'toggleAllDenied'])->name('approval_reflections.toggle_denied');
        // 案件別応募管理
        Route::get('campaigns/{campaign}/applications', [ApplicationController::class, 'campaignIndex'])->name('campaigns.applications');
        // 日別件数管理（全案件一覧）
        Route::get('daily-slots', [CampaignDailySlotController::class, 'listAll'])->name('daily_slots.index');
        Route::post('daily-slots/import', [CampaignDailySlotController::class, 'importBulkTsv'])->name('daily_slots.import');
        // 日別打診予定数管理（案件別）
        Route::get('campaigns/{campaign}/daily-slots', [CampaignDailySlotController::class, 'index'])->name('campaigns.daily_slots.index');
        Route::post('campaigns/{campaign}/daily-slots', [CampaignDailySlotController::class, 'store'])->name('campaigns.daily_slots.store');
        Route::patch('campaigns/{campaign}/daily-slots/{slot}', [CampaignDailySlotController::class, 'update'])->name('campaigns.daily_slots.update');
        Route::delete('campaigns/{campaign}/daily-slots/{slot}', [CampaignDailySlotController::class, 'destroy'])->name('campaigns.daily_slots.destroy');
        Route::post('campaigns/{campaign}/daily-slots/import', [CampaignDailySlotController::class, 'importCsv'])->name('campaigns.daily_slots.import');
        // 応募管理
        Route::get('applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
        Route::patch('applications/{application}/status', [ApplicationController::class, 'updateStatus'])->name('applications.status');
        Route::patch('applications/{application}/notes', [ApplicationController::class, 'updateNotes'])->name('applications.notes');
        Route::patch('applications/{application}/invite-schedule', [ApplicationController::class, 'updateInviteSchedule'])->name('applications.invite_schedule');
        Route::post('applications/{application}/continuation-line', [ApplicationController::class, 'sendContinuationRequest'])->name('applications.continuation_line');
        Route::post('applications/{application}/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::patch('schedules/{schedule}/confirm', [ScheduleController::class, 'confirm'])->name('schedules.confirm');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::patch('reports/{report}/approve', [ReportController::class, 'approve'])->name('reports.approve');
        Route::patch('reports/{report}/reject', [ReportController::class, 'reject'])->name('reports.reject');
        Route::get('agents', [AgentController::class, 'index'])->name('agents.index');
        Route::get('agents/create', [AgentController::class, 'create'])->name('agents.create');
        Route::post('agents', [AgentController::class, 'store'])->name('agents.store');
        Route::get('agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
        Route::post('agents/{agent}/code', [AgentController::class, 'addCode'])->name('agents.add_code');
        Route::delete('agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('referrals', [ReferralController::class, 'index'])->name('referrals.index');
        Route::patch('referrals/mark-done', [ReferralController::class, 'markDone'])->name('referrals.mark_done');
        Route::patch('referrals/mark-pending', [ReferralController::class, 'markPending'])->name('referrals.mark_pending');
        Route::get('referrals/{code}', [ReferralController::class, 'show'])->name('referrals.show');
        Route::get('points', [PointController::class, 'index'])->name('points.index');
        Route::get('points/csv', [PointController::class, 'exportCsv'])->name('points.csv');
        Route::get('points/zengin', [PointController::class, 'exportZengin'])->name('points.zengin');
        Route::patch('points/mark-reserved', [PointController::class, 'markReserved'])->name('points.mark_reserved');
        Route::patch('points/mark-paid', [PointController::class, 'markPaid'])->name('points.mark_paid');
        Route::post('points/adjust', [PointController::class, 'adjust'])->name('points.adjust');
        Route::get('settlements', [SettlementController::class, 'index'])->name('settlements.index');
        Route::get('settlements/{settlement}', [SettlementController::class, 'show'])->name('settlements.show');
        Route::post('settlements/close', [SettlementController::class, 'close'])->name('settlements.close');
        Route::patch('settlements/{settlement}/paid', [SettlementController::class, 'markPaid'])->name('settlements.paid');
        Route::get('notifications/line', [LineNotificationController::class, 'index'])->name('notifications.line');
        Route::post('notifications/line', [LineNotificationController::class, 'send'])->name('notifications.line.send');
        Route::get('import', [ImportController::class, 'index'])->name('import.index');
        Route::post('import/users', [ImportController::class, 'importUsers'])->name('import.users');
        Route::post('import/applications', [ImportController::class, 'importApplications'])->name('import.applications');
        Route::post('import/points', [ImportController::class, 'importPoints'])->name('import.points');
        Route::post('import/campaigns', [ImportController::class, 'importCampaigns'])->name('import.campaigns');

        // キャンペーン管理（協力金ボーナス）
        Route::get('campaign-bonuses', [CampaignBonusController::class, 'index'])->name('campaign_bonuses.index');
        Route::post('campaign-bonuses', [CampaignBonusController::class, 'store'])->name('campaign_bonuses.store');
        Route::delete('campaign-bonuses/{campaignBonus}', [CampaignBonusController::class, 'destroy'])->name('campaign_bonuses.destroy');

        // 回収管理
        Route::get('collection-reports', [CollectionReportController::class, 'index'])->name('collection_reports.index');
        Route::get('collection-reports/{collectionReport}', [CollectionReportController::class, 'show'])->name('collection_reports.show');
        Route::patch('collection-reports/{collectionReport}/approve', [CollectionReportController::class, 'approve'])->name('collection_reports.approve');
        Route::patch('collection-reports/{collectionReport}/reject', [CollectionReportController::class, 'reject'])->name('collection_reports.reject');

        // フォーム項目管理（ページ編集）
        Route::get('form-fields', [FormFieldController::class, 'index'])->name('form_fields.index');
        Route::post('form-fields', [FormFieldController::class, 'store'])->name('form_fields.store');
        Route::patch('form-fields/{formField}', [FormFieldController::class, 'update'])->name('form_fields.update');
        Route::delete('form-fields/{formField}', [FormFieldController::class, 'destroy'])->name('form_fields.destroy');
        Route::patch('form-fields/{formField}/toggle', [FormFieldController::class, 'toggle'])->name('form_fields.toggle');
        Route::patch('form-fields/legal/{slug}', [FormFieldController::class, 'updateLegal'])->name('form_fields.legal');
        // 案件別応募フォームフィールド設定
        Route::post('campaigns/{campaign}/form-fields', [CampaignController::class, 'syncFormFields'])->name('campaigns.form_fields.sync');
    });
});

// ■ 会員（LIFF）ルート
Route::prefix('member')->name('member.')->group(function () {

    // 認証不要
    Route::get('login', [MemberAuth::class, 'login'])->name('login');
    Route::post('auth/liff-callback', [MemberAuth::class, 'liffCallback'])->name('auth.liff');
    Route::post('auth/dev-login', [MemberAuth::class, 'devLogin'])->name('auth.dev');
    Route::post('auth/logout', [MemberAuth::class, 'logout'])->name('logout');

    // LIFF認証必須
    Route::middleware('auth:liff')->group(function () {

        // プロフィール未設定ユーザー用（register は profile チェック除外）
        Route::get('register', [MemberRegister::class, 'show'])->name('register');
        Route::post('register', [MemberRegister::class, 'store'])->name('register.store');

        // プロフィール登録済みユーザー用
        Route::middleware(EnsureProfileCompleted::class)->group(function () {
            Route::get('campaigns', [MemberCampaign::class, 'index'])->name('campaigns.index');
            Route::get('campaigns/complete', [MemberCampaign::class, 'complete'])->name('campaigns.complete');
            Route::get('campaigns/{campaign}', [MemberCampaign::class, 'show'])->name('campaigns.show');
            Route::post('campaigns/{campaign}/apply', [MemberCampaign::class, 'apply'])->name('campaigns.apply');
            Route::get('mypage', [MemberMypage::class, 'index'])->name('mypage');
            Route::get('profile/edit', [MemberRegister::class, 'edit'])->name('profile.edit');
            Route::patch('profile', [MemberRegister::class, 'updateProfile'])->name('profile.update');
            Route::get('reports/create', [\App\Http\Controllers\Member\ReportController::class, 'create'])->name('reports.create');
            Route::post('reports', [\App\Http\Controllers\Member\ReportController::class, 'store'])->name('reports.store');
            Route::post('reports/collection', [\App\Http\Controllers\Member\ReportController::class, 'storeCollection'])->name('reports.store_collection');
        });
    });
});

Route::get('/member', fn() => redirect()->route('member.login'));

// ■ 代理店ポータル
Route::get('/portal/enter/{token}', [PortalAuth::class, 'login'])->name('portal.login');
Route::prefix('portal')->name('portal.')->middleware('portal.auth')->group(function () {
    Route::get('users',           [PortalUser::class,     'index'])->name('users');
    Route::get('reports',         [PortalReport::class,   'index'])->name('reports');
    Route::get('rewards',         [PortalReward::class,   'index'])->name('rewards');
    Route::get('children',        [PortalChild::class,    'index'])->name('children');
    Route::post('children',       [PortalChild::class,    'store'])->name('children.store');
    Route::get('children/create', [PortalChild::class,    'create'])->name('children.create');
    Route::post('children/{child}/code', [PortalChild::class, 'addCode'])->name('children.add_code');
    Route::get('settings',        [PortalSettings::class, 'index'])->name('settings');
    Route::patch('settings',      [PortalSettings::class, 'update'])->name('settings.update');
});
