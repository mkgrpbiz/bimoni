<?php

namespace App\Http\Controllers\Member\Concerns;

use Illuminate\Http\RedirectResponse;

trait RequiresBankInfo
{
    private function ensureBankInfo($user, string $message): ?RedirectResponse
    {
        if ($user->hasCompleteBankInfo()) {
            return null;
        }
        return redirect()->route('member.profile.edit')->with('error', $message);
    }
}
