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
use App\Http\Controllers\Admin\EndCancelSettingController;
use App\Http\Controllers\Admin\FormFieldController;
use App\Http\Controllers\Admin\ManualAdditionController;
use App\Http\Controllers\Admin\AdminManagerController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\LineNotificationController;
use App\Http\Controllers\Member\AuthController as MemberAuth;
use App\Http\Controllers\Member\CampaignController as MemberCampaign;
use App\Http\Controllers\Member\MypageController as MemberMypage;
use App\Http\Controllers\Member\RegisterController as MemberRegister;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\LineLinkController;
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
use App\Http\Controllers\InviteController;
use App\Http\Controllers\Admin\SettlementController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// 招待ページ（認証不要・代理店コード別）
Route::get('/invite/{code}', [InviteController::class, 'show'])->name('invite');

// 継続依頼応答ページ（認証不要・トークンで保護）
Route::prefix('continuation/{token}')->name('continuation.')->group(function () {
    Route::get('/',        [ContinuationController::class, 'confirm'])->name('confirm');
    Route::post('/accept', [ContinuationController::class, 'accept'])->name('accept');
    Route::post('/decline',[ContinuationController::class, 'decline'])->name('decline');
});

// 打診ページ（認証不要・トークンで保護）
Route::prefix('proposals/{token}')->name('proposals.')->group(function () {
    Route::get('/',          [ProposalController::class, 'confirm'])->name('confirm');
    Route::post('/yes',      [ProposalController::class, 'acceptYes'])->name('yes');
    Route::get('/no',        [ProposalController::class, 'declineNo'])->name('no');
    Route::post('/slot',     [ProposalController::class, 'selectSlot'])->name('slot');
    Route::post('/cancel',   [ProposalController::class, 'cancel'])->name('cancel');
    Route::get('/complete',  [ProposalController::class, 'complete'])->name('complete');
    Route::post('/revert',   [ProposalController::class, 'revert'])->name('revert');
});

Route::prefix('admin')->name('admin.')->group(function () {
    require __DIR__.'/auth.php';

    Route::middleware('auth:web')->group(function () {
        Route::get('/', fn() => redirect()->route('admin.dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/alerts/dismiss', [DashboardController::class, 'dismissAlert'])->name('alerts.dismiss');
        Route::get('campaigns/search', [CampaignController::class, 'search'])->name('campaigns.search');
        Route::resource('campaigns', CampaignController::class);
        Route::post('campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicate'])->name('campaigns.duplicate');
        Route::post('campaigns/reorder', [CampaignController::class, 'reorder'])->name('campaigns.reorder');
        Route::patch('campaigns/{campaign}/toggle-visible', [CampaignController::class, 'toggleVisible'])->name('campaigns.toggle_visible');
        Route::patch('campaigns/{campaign}/status', [CampaignController::class, 'updateStatus'])->name('campaigns.update_status');
        // 解約方法管理
        Route::get('cancellation-settings', [\App\Http\Controllers\Admin\CancellationSettingController::class, 'index'])->name('cancellation_settings.index');
        Route::get('cancellation-settings/{campaign}/edit', [\App\Http\Controllers\Admin\CancellationSettingController::class, 'edit'])->name('cancellation_settings.edit');
        Route::put('cancellation-settings/{campaign}', [\App\Http\Controllers\Admin\CancellationSettingController::class, 'update'])->name('cancellation_settings.update');
        Route::patch('cancellation-settings/{campaign}/toggle-visible', [\App\Http\Controllers\Admin\CancellationSettingController::class, 'toggleVisible'])->name('cancellation_settings.toggle_visible');
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
        Route::get('proposal-reservations', [ApplicationController::class, 'proposalReservationIndex'])->name('proposal_reservations.index');
        Route::get('manual-addition', [ManualAdditionController::class, 'index'])->name('manual_addition.index');
        Route::post('manual-addition', [ManualAdditionController::class, 'store'])->name('manual_addition.store');
        Route::post('applications/{application}/re-proposal', [ApplicationController::class, 'sendReProposal'])->name('applications.re_proposal');
        Route::get('applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
        Route::patch('applications/{application}/status', [ApplicationController::class, 'updateStatus'])->name('applications.status');
        Route::patch('applications/{application}/notes', [ApplicationController::class, 'updateNotes'])->name('applications.notes');
        Route::patch('applications/{application}/invite-schedule', [ApplicationController::class, 'updateInviteSchedule'])->name('applications.invite_schedule');
        Route::post('applications/{application}/continuation-line', [ApplicationController::class, 'sendContinuationRequest'])->name('applications.continuation_line');
        Route::patch('applications/{application}/continuation', [ApplicationController::class, 'updateContinuation'])->name('applications.continuation_update');
        Route::patch('applications/{application}/course', [ApplicationController::class, 'updateCourse'])->name('applications.course_update');
        Route::post('applications/{application}/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::patch('schedules/{schedule}/confirm', [ScheduleController::class, 'confirm'])->name('schedules.confirm');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::patch('reports/{report}/approve', [ReportController::class, 'approve'])->name('reports.approve');
        Route::patch('reports/{report}/reject', [ReportController::class, 'reject'])->name('reports.reject');
        Route::patch('reports/{report}/revert', [ReportController::class, 'revert'])->name('reports.revert');
        Route::patch('reports/{report}/adjust', [ReportController::class, 'adjust'])->name('reports.adjust');
        Route::patch('reports/{report}/purchase-type', [ReportController::class, 'updatePurchaseType'])->name('reports.purchase_type');
        Route::patch('reports/{report}/campaign', [ReportController::class, 'updateCampaign'])->name('reports.campaign');
        Route::get('agents', [AgentController::class, 'index'])->name('agents.index');
        Route::get('agents/create', [AgentController::class, 'create'])->name('agents.create');
        Route::post('agents', [AgentController::class, 'store'])->name('agents.store');
        Route::get('agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
        Route::patch('agents/{agent}', [AgentController::class, 'update'])->name('agents.update');
        Route::post('agents/{agent}/code', [AgentController::class, 'addCode'])->name('agents.add_code');
        Route::delete('agents/code/{code}', [AgentController::class, 'deleteCode'])->name('agents.delete_code');
        Route::delete('agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('line-links', [LineLinkController::class, 'index'])->name('line_links.index');
        Route::get('line-links/search', [LineLinkController::class, 'searchLiff'])->name('line_links.search');
        Route::get('line-links/search-import', [LineLinkController::class, 'searchImport'])->name('line_links.search_import');
        Route::post('line-links/link', [LineLinkController::class, 'link'])->name('line_links.link');
        Route::post('line-links/skip', [LineLinkController::class, 'skip'])->name('line_links.skip');
        Route::post('line-links/confirm-new', [LineLinkController::class, 'confirmNew'])->name('line_links.confirm_new');
        Route::get('referrals', [ReferralController::class, 'index'])->name('referrals.index');
        Route::patch('referrals/mark-done', [ReferralController::class, 'markDone'])->name('referrals.mark_done');
        Route::patch('referrals/mark-pending', [ReferralController::class, 'markPending'])->name('referrals.mark_pending');
        Route::get('referrals/{agent}', [ReferralController::class, 'show'])->name('referrals.show');
        Route::get('referrals/{agent}/csv', [ReferralController::class, 'exportCsv'])->name('referrals.csv');
        Route::get('points', [PointController::class, 'index'])->name('points.index');
        Route::get('points/csv', [PointController::class, 'exportCsv'])->name('points.csv');
        Route::get('points/zengin', [PointController::class, 'exportZengin'])->name('points.zengin');
        Route::patch('points/grant/{report}', [PointController::class, 'grant'])->name('points.grant');
        Route::patch('points/mark-reserved', [PointController::class, 'markReserved'])->name('points.mark_reserved');
        Route::patch('points/mark-paid', [PointController::class, 'markPaid'])->name('points.mark_paid');
        Route::post('points/adjust', [PointController::class, 'adjust'])->name('points.adjust');
        Route::get('settlements', [SettlementController::class, 'index'])->name('settlements.index');
        Route::get('settlements/{settlement}', [SettlementController::class, 'show'])->name('settlements.show');
        Route::post('settlements/close', [SettlementController::class, 'close'])->name('settlements.close');
        Route::patch('settlements/{settlement}/paid', [SettlementController::class, 'markPaid'])->name('settlements.paid');
        Route::get('notifications/line', [LineNotificationController::class, 'index'])->name('notifications.line');
        Route::post('notifications/line', [LineNotificationController::class, 'send'])->name('notifications.line.send');
        Route::post('notifications/line/{notification}/resend', [LineNotificationController::class, 'resend'])->name('notifications.line.resend');
        Route::post('notifications/line/{notification}/resolve', [LineNotificationController::class, 'resolve'])->name('notifications.line.resolve');
        Route::get('import', [ImportController::class, 'index'])->name('import.index');
        Route::post('import/users', [ImportController::class, 'importUsers'])->name('import.users');
        Route::post('import/reports', [ImportController::class, 'importReports'])->name('import.reports');
        Route::post('import/campaigns', [ImportController::class, 'importCampaigns'])->name('import.campaigns');
        Route::post('import/collections', [ImportController::class, 'importCollections'])->name('import.collections');
        Route::post('import/applications', [ImportController::class, 'importApplications'])->name('import.applications');

        // 管理者管理
        Route::get('admins', [AdminManagerController::class, 'index'])->name('admins.index');
        Route::get('admins/create', [AdminManagerController::class, 'create'])->name('admins.create');
        Route::post('admins', [AdminManagerController::class, 'store'])->name('admins.store');
        Route::get('admins/{admin}/edit', [AdminManagerController::class, 'edit'])->name('admins.edit');
        Route::patch('admins/{admin}', [AdminManagerController::class, 'update'])->name('admins.update');
        Route::delete('admins/{admin}', [AdminManagerController::class, 'destroy'])->name('admins.destroy');
        Route::post('admins/{admin}/reset-password', [AdminManagerController::class, 'resetPassword'])->name('admins.reset_password');

        // 自分のプロフィール（パスワード・メール変更）
        Route::get('profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [AdminProfileController::class, 'update'])->name('profile.update');

        // キャンペーン管理（協力金ボーナス）
        Route::get('campaign-bonuses', [CampaignBonusController::class, 'index'])->name('campaign_bonuses.index');
        Route::post('campaign-bonuses', [CampaignBonusController::class, 'store'])->name('campaign_bonuses.store');
        Route::delete('campaign-bonuses/{campaignBonus}', [CampaignBonusController::class, 'destroy'])->name('campaign_bonuses.destroy');

        // 回収管理
        Route::get('collection-reports', [CollectionReportController::class, 'index'])->name('collection_reports.index');
        Route::get('collection-reports/{collectionReport}', [CollectionReportController::class, 'show'])->name('collection_reports.show');
        Route::patch('collection-reports/{collectionReport}/approve', [CollectionReportController::class, 'approve'])->name('collection_reports.approve');
        Route::patch('collection-reports/{collectionReport}/reject', [CollectionReportController::class, 'reject'])->name('collection_reports.reject');
        Route::patch('collection-reports/{collectionReport}/revert', [CollectionReportController::class, 'revert'])->name('collection_reports.revert');
        Route::patch('collection-reports/{collectionReport}/adjust', [CollectionReportController::class, 'adjust'])->name('collection_reports.adjust');

        // フォーム項目管理（ページ編集）
        Route::get('form-fields', [FormFieldController::class, 'index'])->name('form_fields.index');
        Route::post('form-fields', [FormFieldController::class, 'store'])->name('form_fields.store');
        Route::patch('form-fields/{formField}', [FormFieldController::class, 'update'])->name('form_fields.update');
        Route::delete('form-fields/{formField}', [FormFieldController::class, 'destroy'])->name('form_fields.destroy');
        Route::patch('form-fields/{formField}/toggle', [FormFieldController::class, 'toggle'])->name('form_fields.toggle');
        Route::patch('form-fields/legal/{slug}', [FormFieldController::class, 'updateLegal'])->name('form_fields.legal');
        Route::patch('form-fields/end-cancel-setting', [EndCancelSettingController::class, 'update'])->name('form_fields.end_cancel_setting');
        // よくある質問管理
        Route::get('faqs', [\App\Http\Controllers\Admin\FaqController::class, 'index'])->name('faqs.index');
        Route::get('faqs/create', [\App\Http\Controllers\Admin\FaqController::class, 'create'])->name('faqs.create');
        Route::post('faqs', [\App\Http\Controllers\Admin\FaqController::class, 'store'])->name('faqs.store');
        Route::get('faqs/{faq}/edit', [\App\Http\Controllers\Admin\FaqController::class, 'edit'])->name('faqs.edit');
        Route::put('faqs/{faq}', [\App\Http\Controllers\Admin\FaqController::class, 'update'])->name('faqs.update');
        Route::delete('faqs/{faq}', [\App\Http\Controllers\Admin\FaqController::class, 'destroy'])->name('faqs.destroy');
        Route::patch('faqs/{faq}/toggle-visible', [\App\Http\Controllers\Admin\FaqController::class, 'toggleVisible'])->name('faqs.toggle_visible');
        Route::post('faqs/reorder', [\App\Http\Controllers\Admin\FaqController::class, 'reorder'])->name('faqs.reorder');
        // ガイドページ管理（初心者ガイド・回収サービス等）
        Route::get('guide-pages', [\App\Http\Controllers\Admin\GuidePageController::class, 'index'])->name('guide_pages.index');
        Route::get('guide-pages/create', [\App\Http\Controllers\Admin\GuidePageController::class, 'create'])->name('guide_pages.create');
        Route::post('guide-pages', [\App\Http\Controllers\Admin\GuidePageController::class, 'store'])->name('guide_pages.store');
        Route::get('guide-pages/{guidePage}/edit', [\App\Http\Controllers\Admin\GuidePageController::class, 'edit'])->name('guide_pages.edit');
        Route::put('guide-pages/{guidePage}', [\App\Http\Controllers\Admin\GuidePageController::class, 'update'])->name('guide_pages.update');
        Route::delete('guide-pages/{guidePage}', [\App\Http\Controllers\Admin\GuidePageController::class, 'destroy'])->name('guide_pages.destroy');
        Route::patch('guide-pages/{guidePage}/toggle-visible', [\App\Http\Controllers\Admin\GuidePageController::class, 'toggleVisible'])->name('guide_pages.toggle_visible');
        Route::post('guide-pages/reorder', [\App\Http\Controllers\Admin\GuidePageController::class, 'reorder'])->name('guide_pages.reorder');
        Route::post('guide-pages/{guidePage}/sections', [\App\Http\Controllers\Admin\GuideSectionController::class, 'store'])->name('guide_sections.store');
        Route::get('guide-sections/{guideSection}/edit', [\App\Http\Controllers\Admin\GuideSectionController::class, 'edit'])->name('guide_sections.edit');
        Route::put('guide-sections/{guideSection}', [\App\Http\Controllers\Admin\GuideSectionController::class, 'update'])->name('guide_sections.update');
        Route::delete('guide-sections/{guideSection}', [\App\Http\Controllers\Admin\GuideSectionController::class, 'destroy'])->name('guide_sections.destroy');
        Route::patch('guide-sections/{guideSection}/toggle-visible', [\App\Http\Controllers\Admin\GuideSectionController::class, 'toggleVisible'])->name('guide_sections.toggle_visible');
        Route::post('guide-sections/reorder', [\App\Http\Controllers\Admin\GuideSectionController::class, 'reorder'])->name('guide_sections.reorder');
        Route::post('guide-sections/{guideSection}/notes', [\App\Http\Controllers\Admin\GuideNoteController::class, 'store'])->name('guide_notes.store');
        Route::put('guide-notes/{guideNote}', [\App\Http\Controllers\Admin\GuideNoteController::class, 'update'])->name('guide_notes.update');
        Route::delete('guide-notes/{guideNote}', [\App\Http\Controllers\Admin\GuideNoteController::class, 'destroy'])->name('guide_notes.destroy');
        Route::post('guide-notes/reorder', [\App\Http\Controllers\Admin\GuideNoteController::class, 'reorder'])->name('guide_notes.reorder');
        Route::post('guide-sections/{guideSection}/steps', [\App\Http\Controllers\Admin\GuideStepController::class, 'store'])->name('guide_steps.store');
        Route::put('guide-steps/{guideStep}', [\App\Http\Controllers\Admin\GuideStepController::class, 'update'])->name('guide_steps.update');
        Route::delete('guide-steps/{guideStep}', [\App\Http\Controllers\Admin\GuideStepController::class, 'destroy'])->name('guide_steps.destroy');
        Route::patch('guide-steps/{guideStep}/toggle-visible', [\App\Http\Controllers\Admin\GuideStepController::class, 'toggleVisible'])->name('guide_steps.toggle_visible');
        Route::post('guide-steps/reorder', [\App\Http\Controllers\Admin\GuideStepController::class, 'reorder'])->name('guide_steps.reorder');
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

    // LINEメニューから直接開く公開ページ（ログイン不要）
    Route::get('faq', [\App\Http\Controllers\Member\FaqController::class, 'index'])->name('faq');
    Route::get('guide/{guidePage:slug}', [\App\Http\Controllers\Member\GuideController::class, 'show'])->name('guide');

    // LIFF認証必須
    Route::middleware('auth:liff')->group(function () {

        // プロフィール未設定ユーザー用（register は profile チェック除外）
        Route::get('register', [MemberRegister::class, 'show'])->name('register');
        Route::post('register', [MemberRegister::class, 'store'])->name('register.store');
        Route::get('transfer', [\App\Http\Controllers\Member\TransferController::class, 'show'])->name('transfer');
        Route::post('transfer', [\App\Http\Controllers\Member\TransferController::class, 'store'])->name('transfer.store');

        // プロフィール登録済みユーザー用
        Route::middleware(EnsureProfileCompleted::class)->group(function () {
            Route::get('campaigns', [MemberCampaign::class, 'index'])->name('campaigns.index');
            Route::get('campaigns/complete', [MemberCampaign::class, 'complete'])->name('campaigns.complete');
            Route::get('campaigns/{campaign}', [MemberCampaign::class, 'show'])->name('campaigns.show');
            Route::post('campaigns/{campaign}/apply', [MemberCampaign::class, 'apply'])->name('campaigns.apply');
            Route::get('mypage', [MemberMypage::class, 'index'])->name('mypage');
            Route::get('cancellations', [\App\Http\Controllers\Member\CancellationController::class, 'index'])->name('cancellations');
            Route::get('profile/edit', [MemberRegister::class, 'edit'])->name('profile.edit');
            Route::patch('profile', [MemberRegister::class, 'updateProfile'])->name('profile.update');
            Route::get('reports/create', [\App\Http\Controllers\Member\ReportController::class, 'create'])->name('reports.create');
            Route::get('collections/create', [\App\Http\Controllers\Member\ReportController::class, 'createCollection'])->name('collections.create');
            Route::get('reports/{report}', [\App\Http\Controllers\Member\ReportController::class, 'show'])->name('reports.show');
            Route::get('reports/collection/{collectionReport}', [\App\Http\Controllers\Member\ReportController::class, 'showCollection'])->name('reports.show_collection');
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
    Route::post('children/{child}/code',    [PortalChild::class, 'addCode'])->name('children.add_code');
    Route::patch('children/{child}/reward', [PortalChild::class, 'updateReward'])->name('children.update_reward');
    Route::delete('children/code/{code}',  [PortalChild::class, 'deleteCode'])->name('children.delete_code');
    Route::get('settings',        [PortalSettings::class, 'index'])->name('settings');
    Route::patch('settings',      [PortalSettings::class, 'update'])->name('settings.update');
});
