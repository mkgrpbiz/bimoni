<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContinuationController extends Controller
{
    public function confirm(string $token): View|\Illuminate\Http\Response
    {
        $application = Application::where('continuation_token', $token)
            ->with(['campaign', 'user'])
            ->first();

        if (!$application || $application->continuation_response !== null || $this->isExpired($application)) {
            return response()->view('proposals.expired', [], 410);
        }

        return view('continuation.confirm', compact('application'));
    }

    public function accept(string $token): View|\Illuminate\Http\Response
    {
        $application = Application::where('continuation_token', $token)
            ->with(['campaign'])
            ->firstOrFail();

        if ($application->continuation_response === null) {
            if ($this->isExpired($application)) {
                return response()->view('proposals.expired', [], 410);
            }
            $application->update([
                'continuation_response'      => 'possible',
                'continuation_responded_at'  => now(),
            ]);
        }

        return view('continuation.accepted', compact('application'));
    }

    public function decline(string $token): View|\Illuminate\Http\Response
    {
        $application = Application::where('continuation_token', $token)
            ->with(['campaign'])
            ->firstOrFail();

        if ($application->continuation_response === null) {
            if ($this->isExpired($application)) {
                return response()->view('proposals.expired', [], 410);
            }
            $application->update([
                'continuation_response'      => 'not_possible',
                'continuation_responded_at'  => now(),
            ]);
        }

        return view('continuation.declined', compact('application'));
    }

    // 送信から24時間経過した継続依頼は回答不可（自動NG化はcontinuations:auto-declineコマンドが担当）
    private function isExpired(Application $application): bool
    {
        return $application->continuation_sent_at !== null
            && $application->continuation_sent_at->addHours(24)->isPast();
    }
}
