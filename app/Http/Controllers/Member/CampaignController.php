<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationFormResponse;
use App\Models\Campaign;
use App\Models\FormField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        $user      = Auth::guard('liff')->user();
        $campaigns = Campaign::where('status', 'published')
            ->with('category')
            ->latest()
            ->get();

        $appliedIds = Application::where('user_id', $user->id)
            ->whereIn('campaign_id', $campaigns->pluck('id'))
            ->pluck('status', 'campaign_id');

        return view('member.campaigns.index', compact('campaigns', 'appliedIds'));
    }

    public function show(Campaign $campaign): View|RedirectResponse
    {
        if ($campaign->status !== 'published') {
            abort(404);
        }

        $user        = Auth::guard('liff')->user();
        $application = Application::where('user_id', $user->id)
            ->where('campaign_id', $campaign->id)
            ->first();
        $appFields = FormField::forType('application')->visible()->get();

        return view('member.campaigns.show', compact('campaign', 'application', 'appFields'));
    }

    public function apply(Request $request, Campaign $campaign): RedirectResponse
    {
        if ($campaign->status !== 'published') {
            abort(404);
        }

        $user = Auth::guard('liff')->user();

        // 重複応募チェック
        $exists = Application::where('user_id', $user->id)
            ->where('campaign_id', $campaign->id)
            ->exists();

        if ($exists) {
            return redirect()->route('member.campaigns.show', $campaign)
                ->with('error', 'すでに応募済みです。');
        }

        // グローバル応募フォームフィールドでバリデーション（campaign_* 表示用は除外）
        $appFields = FormField::forType('application')->visible()->get()
            ->reject(fn($f) => str_starts_with($f->type, 'campaign_'));

        $userUpdates = [];

        $rules = [];
        foreach ($appFields as $field) {
            $key = 'field_' . $field->field_key;
            $rules[$key] = ($field->is_required ? 'required' : 'nullable')
                . ($field->type === 'image' ? '|image|max:10240' : '');
        }
        $request->validate($rules);

        $application = Application::create([
            'user_id'     => $user->id,
            'campaign_id' => $campaign->id,
            'status'      => 'pending',
            'applied_at'  => now(),
        ]);

        // 回答保存
        foreach ($appFields as $field) {
            $key = 'field_' . $field->field_key;
            if ($field->type === 'image') {
                if ($request->hasFile($key)) {
                    $path = $request->file($key)->store('form_images', 'public');
                    ApplicationFormResponse::create([
                        'application_id' => $application->id,
                        'field_key'      => $field->field_key,
                        'value'          => $path,
                    ]);
                }
            } else {
                $value = $request->input($key);
                if ($value !== null) {
                    $storedValue = is_array($value) ? implode(',', $value) : $value;
                    ApplicationFormResponse::create([
                        'application_id' => $application->id,
                        'field_key'      => $field->field_key,
                        'value'          => $storedValue,
                    ]);
                    // application_* タイプはユーザープロファイルにも反映
                    if ($field->type === 'application_wants_continuation') {
                        $userUpdates['wants_continuation'] = (bool) $storedValue;
                    }
                    if ($field->type === 'application_available_times') {
                        $userUpdates['available_times'] = is_array($value) ? $value : explode(',', $storedValue);
                    }
                }
            }
        }

        if (!empty($userUpdates)) {
            $user->update($userUpdates);
        }

        return redirect()->route('member.campaigns.show', $campaign)
            ->with('success', '応募が完了しました！');
    }
}
