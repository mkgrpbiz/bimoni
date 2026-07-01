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

        if (!$application || $application->continuation_response !== null) {
            return response()->view('proposals.expired', [], 410);
        }

        return view('continuation.confirm', compact('application'));
    }

    public function accept(string $token): View
    {
        $application = Application::where('continuation_token', $token)
            ->with(['campaign'])
            ->firstOrFail();

        if ($application->continuation_response === null) {
            $application->update([
                'continuation_response'      => 'possible',
                'continuation_responded_at'  => now(),
            ]);
        }

        return view('continuation.accepted', compact('application'));
    }

    public function decline(string $token): View
    {
        $application = Application::where('continuation_token', $token)
            ->with(['campaign'])
            ->firstOrFail();

        if ($application->continuation_response === null) {
            $application->update([
                'continuation_response'      => 'impossible',
                'continuation_responded_at'  => now(),
            ]);
        }

        return view('continuation.declined', compact('application'));
    }
}
