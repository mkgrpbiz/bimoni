<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\LegalPage;
use App\Models\UserFormResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = Auth::guard('liff')->user();
        if ($user->profile_completed_at) {
            return redirect()->route('member.campaigns.index');
        }

        $fields  = FormField::forType('registration')->visible()->get();
        $terms   = LegalPage::terms();
        $privacy = LegalPage::privacy();

        return view('member.register', compact('fields', 'user', 'terms', 'privacy'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user   = Auth::guard('liff')->user();
        $fields = FormField::forType('registration')->visible()->get();

        $rules = $this->buildRules($fields);
        $rules['agree_terms'] = 'accepted';

        // 銀行口座バリデーション（任意だが、入力した場合は必須セットで）
        $rules['bank_account_number'] = 'nullable|digits_between:7,8';

        $request->validate($rules, ['agree_terms.accepted' => '利用規約とプライバシーポリシーへの同意が必要です。']);

        $this->saveFields($user, $fields, $request);
        $this->saveBank($user, $request);

        $user->update(['profile_completed_at' => now()]);

        return redirect()->route('member.campaigns.index');
    }

    public function edit(): View
    {
        $user    = Auth::guard('liff')->user();
        $fields  = FormField::forType('registration')->visible()->get();
        $responses = UserFormResponse::where('user_id', $user->id)->pluck('value', 'field_key');

        return view('member.profile.edit', compact('user', 'fields', 'responses'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user   = Auth::guard('liff')->user();
        $fields = FormField::forType('registration')->visible()->get();

        $rules = $this->buildRules($fields);
        $rules['bank_account_number'] = 'nullable|digits_between:7,8';

        $request->validate($rules);

        $this->saveFields($user, $fields, $request);
        $this->saveBank($user, $request);

        return back()->with('success', 'プロフィールを更新しました。');
    }

    private function buildRules($fields): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $key = 'field_' . $field->field_key;
            $rules[$key] = $field->is_required ? 'required' : 'nullable';
            if ($field->type === 'email')    $rules[$key] .= '|email';
            if ($field->type === 'tel')      $rules[$key] .= '|regex:/^[0-9\-\(\)\+]{7,15}$/';
            if ($field->type === 'date')     $rules[$key] .= '|date';
            if ($field->type === 'checkbox') $rules[$key] = $field->is_required ? 'required|array|min:1' : 'nullable|array';
        }
        return $rules;
    }

    private function saveFields($user, $fields, Request $request): void
    {
        $userColumns = [];
        foreach ($fields as $field) {
            $inputKey = 'field_' . $field->field_key;
            $value    = $request->input($inputKey);

            if ($field->maps_to) {
                $userColumns[$field->maps_to] = is_array($value) ? implode(',', $value) : $value;
            } else {
                UserFormResponse::updateOrCreate(
                    ['user_id' => $user->id, 'field_key' => $field->field_key],
                    ['value' => is_array($value) ? implode(',', $value) : $value]
                );
            }
        }
        if ($userColumns) $user->update($userColumns);
    }

    private function saveBank($user, Request $request): void
    {
        $user->update([
            'bank_name'           => $request->input('bank_name'),
            'bank_code'           => $request->input('bank_code'),
            'bank_branch_name'    => $request->input('bank_branch_name'),
            'bank_branch_code'    => $request->input('bank_branch_code'),
            'bank_account_type'   => $request->input('bank_account_type'),
            'bank_account_number' => $request->input('bank_account_number'),
            'bank_account_name'   => $request->input('bank_account_name'),
        ]);
    }
}
