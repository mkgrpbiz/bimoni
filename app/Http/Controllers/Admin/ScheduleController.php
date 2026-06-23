<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function store(Request $request, Application $application): RedirectResponse
    {
        $request->validate([
            'proposed_dates'   => 'required|array|min:1',
            'proposed_dates.*' => 'required|date',
            'notes'            => 'nullable|string',
        ]);

        $application->schedules()->where('status', 'proposing')->update(['status' => 'cancelled']);

        ApplicationSchedule::create([
            'application_id' => $application->id,
            'proposed_dates' => $request->proposed_dates,
            'status'         => 'proposing',
            'proposed_by'    => Auth::guard('web')->id(),
            'notes'          => $request->notes,
        ]);

        $application->update(['status' => 'line_contacted', 'line_contacted_at' => now(), 'line_contact_status' => 'sent']);

        return back()->with('success', '打診日程を登録しました。');
    }

    public function confirm(Request $request, ApplicationSchedule $schedule): RedirectResponse
    {
        $request->validate(['confirmed_datetime' => 'required|date']);

        $schedule->update([
            'confirmed_datetime' => $request->confirmed_datetime,
            'status'             => 'confirmed',
        ]);

        $schedule->application->update([
            'status'                 => 'scheduled',
            'schedule_confirmed_at'  => now(),
        ]);

        return back()->with('success', '日程を確定しました。');
    }
}
