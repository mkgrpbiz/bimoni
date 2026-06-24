<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\FormField;
use App\Models\MonitorReport;
use App\Models\MonitorReportImage;
use App\Models\ReportFormResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function create(Request $request): View
    {
        $user = Auth::guard('liff')->user();

        $applications = Application::where('user_id', $user->id)
            ->whereIn('status', ['implementing', 'approved', 'completed'])
            ->with('campaign')
            ->get()
            ->filter(fn($a) => $a->campaign !== null);

        $selectedAppId = $request->input('application_id');
        $selectedApp   = $selectedAppId ? $applications->firstWhere('id', $selectedAppId) : null;
        $reportFields  = $selectedApp
            ? FormField::forType('report')->visible()->get()
            : collect();

        return view('member.reports.create', compact('applications', 'selectedApp', 'reportFields'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::guard('liff')->user();

        $request->validate([
            'application_id'  => 'required|exists:applications,id',
            'report_images'   => 'nullable|array|max:3',
            'report_images.*' => 'image|max:10240',
        ]);

        $application = Application::where('id', $request->application_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (MonitorReport::where('application_id', $application->id)->exists()) {
            return back()->with('error', 'この案件の報告は既に送信済みです。');
        }

        $report = MonitorReport::create([
            'user_id'        => $user->id,
            'campaign_id'    => $application->campaign_id,
            'application_id' => $application->id,
            'status'         => 'pending',
        ]);

        // 複数画像保存
        if ($request->hasFile('report_images')) {
            foreach ($request->file('report_images') as $i => $file) {
                if ($file->isValid()) {
                    $path = $file->store('reports', 'public');
                    MonitorReportImage::create([
                        'monitor_report_id' => $report->id,
                        'image_path'        => $path,
                        'sort_order'        => $i,
                    ]);
                }
            }
        }

        // 動的フィールド保存
        $reportFields = FormField::forType('report')->visible()->get();
        foreach ($reportFields as $field) {
            $key = 'field_' . $field->field_key;
            if ($field->type === 'image') {
                if ($request->hasFile($key)) {
                    $path = $request->file($key)->store('form_images', 'public');
                    ReportFormResponse::create([
                        'monitor_report_id' => $report->id,
                        'field_key'         => $field->field_key,
                        'value'             => $path,
                    ]);
                }
            } else {
                $value = $request->input($key);
                if ($value !== null) {
                    ReportFormResponse::create([
                        'monitor_report_id' => $report->id,
                        'field_key'         => $field->field_key,
                        'value'             => is_array($value) ? implode(',', $value) : $value,
                    ]);
                }
            }
        }

        return redirect()->route('member.mypage')->with('success', '報告を送信しました。審査をお待ちください。');
    }
}
