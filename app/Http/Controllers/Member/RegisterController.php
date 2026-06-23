<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\FormField;
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

        $fields = FormField::visible()->get();

        return view('member.register', compact('fields', 'user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user   = Auth::guard('liff')->user();
        $fields = FormField::visible()->get();

        // バリデーションルール動的生成
        $rules = [];
        foreach ($fields as $field) {
            $key   = 'field_' . $field->field_key;
            $rules[$key] = $field->is_required ? 'required' : 'nullable';

            if ($field->type === 'email') $rules[$key] .= '|email';
            if ($field->type === 'tel')   $rules[$key] .= '|regex:/^[0-9\-\(\)\+]{7,15}$/';
            if ($field->type === 'date')  $rules[$key] .= '|date';
            if ($field->type === 'checkbox') {
                $rules[$key] = $field->is_required ? 'required|array|min:1' : 'nullable|array';
            }
        }

        $validated = $request->validate($rules);

        $userColumns = [];
        foreach ($fields as $field) {
            $inputKey = 'field_' . $field->field_key;
            $value    = $request->input($inputKey);

            if ($field->maps_to) {
                // usersテーブルのカラムに保存
                if ($field->type === 'checkbox') {
                    $userColumns[$field->maps_to] = $value ?? [];
                } else {
                    $userColumns[$field->maps_to] = $value;
                }
            } else {
                // user_form_responsesに保存
                UserFormResponse::updateOrCreate(
                    ['user_id' => $user->id, 'field_key' => $field->field_key],
                    ['value' => is_array($value) ? implode(',', $value) : $value]
                );
            }
        }

        $userColumns['profile_completed_at'] = now();
        $user->update($userColumns);

        return redirect()->route('member.campaigns.index');
    }
}
