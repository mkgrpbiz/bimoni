<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineNotification;
use App\Models\User;
use App\Services\LineMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LineNotificationController extends Controller
{
    public function __construct(private LineMessagingService $line) {}

    public function index(): View
    {
        $logs  = LineNotification::with('user')->latest('sent_at')->paginate(30);
        $users = User::whereNotNull('name')->orderBy('name')->get();

        return view('admin.notifications.line', compact('logs', 'users'));
    }

    public function resend(LineNotification $notification): RedirectResponse
    {
        $user = $notification->user;
        if (!$user || !$user->line_user_id) {
            return back()->with('error', 'LINE IDが未設定のため再送信できません。');
        }

        // sendPush()を使うと再送信のたびに新しいログ行が作られてしまう（この画面は既存の1行を
        // 更新する設計のため）。HTTPリクエスト自体はこれまで通り直書きのまま、実際のエラー内容
        // だけはLineMessagingService::sendPush()と同じ形式でerror_messageに残すようにする
        // （2026-10-30修正。以前はエラー内容を一切記録しておらず原因調査ができなかった）
        $token = config('services.line.channel_access_token');
        $response = \Illuminate\Support\Facades\Http::withToken($token)
            ->post('https://api.line.me/v2/bot/message/push', [
                'to'       => $user->line_user_id,
                'messages' => [['type' => 'text', 'text' => $notification->message]],
            ]);

        $errorMessage = $response->successful()
            ? null
            : $response->status() . ': ' . ($response->json('message') ?? $response->body());

        $notification->update([
            'status'        => $response->successful() ? 'sent' : 'failed',
            'error_message' => $errorMessage,
            'sent_at'       => now(),
        ]);

        return back()->with('success', $response->successful() ? '再送信しました。' : '再送信に失敗しました。');
    }

    public function resolve(LineNotification $notification): RedirectResponse
    {
        $notification->update(['status' => 'resolved']);
        return back()->with('success', '対応済みにしました。');
    }

    // 個別送信
    public function send(Request $request): RedirectResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'message'  => 'required|string|max:2000',
        ]);

        $results = $this->line->sendBulk($request->user_ids, $request->message);

        return back()->with('success', "送信完了：成功 {$results['sent']} 件 / 失敗 {$results['failed']} 件");
    }
}
