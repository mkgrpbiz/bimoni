<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CampaignDailySlotController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FormFieldController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\LineNotificationController;
use App\Http\Controllers\Member\AuthController as MemberAuth;
use App\Http\Controllers\Member\CampaignController as MemberCampaign;
use App\Http\Controllers\Member\MypageController as MemberMypage;
use App\Http\Controllers\Member\RegisterController as MemberRegister;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Controllers\Admin\PointController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SettlementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
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
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('campaigns', CampaignController::class);
        // 案件別応募管理
        Route::get('campaigns/{campaign}/applications', [ApplicationController::class, 'campaignIndex'])->name('campaigns.applications');
        // 日別打診予定数管理
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
        Route::post('applications/{application}/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::patch('schedules/{schedule}/confirm', [ScheduleController::class, 'confirm'])->name('schedules.confirm');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::patch('reports/{report}/approve', [ReportController::class, 'approve'])->name('reports.approve');
        Route::patch('reports/{report}/reject', [ReportController::class, 'reject'])->name('reports.reject');
        Route::get('points', [PointController::class, 'index'])->name('points.index');
        Route::patch('reports/{report}/grant', [PointController::class, 'grantForReport'])->name('points.grant');
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

        // フォーム項目管理
        Route::get('form-fields', [FormFieldController::class, 'index'])->name('form_fields.index');
        Route::post('form-fields', [FormFieldController::class, 'store'])->name('form_fields.store');
        Route::patch('form-fields/{formField}', [FormFieldController::class, 'update'])->name('form_fields.update');
        Route::delete('form-fields/{formField}', [FormFieldController::class, 'destroy'])->name('form_fields.destroy');
        Route::patch('form-fields/{formField}/toggle', [FormFieldController::class, 'toggle'])->name('form_fields.toggle');
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
            Route::get('campaigns/{campaign}', [MemberCampaign::class, 'show'])->name('campaigns.show');
            Route::post('campaigns/{campaign}/apply', [MemberCampaign::class, 'apply'])->name('campaigns.apply');
            Route::get('mypage', [MemberMypage::class, 'index'])->name('mypage');
        });
    });
});

Route::get('/member', fn() => redirect()->route('member.login'));
