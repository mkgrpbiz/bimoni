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
