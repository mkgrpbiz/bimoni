<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EndCancelSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EndCancelSettingController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'send_start_hour'  => 'required|integer|min:0|max:23',
            'send_end_hour'    => 'required|integer|min:0|max:23',
            'message_template' => 'nullable|string',
        ]);

        EndCancelSetting::current()->update([
            'send_start_hour'  => $request->send_start_hour,
            'send_end_hour'    => $request->send_end_hour,
            'message_template' => $request->message_template,
        ]);

        return back()->with('success', '更新しました。');
    }
}
