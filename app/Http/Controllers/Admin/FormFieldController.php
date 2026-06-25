<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use App\Models\LegalPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormFieldController extends Controller
{
    public function index(Request $request): View
    {
        $tab    = $request->get('tab', 'application');
        $fields = FormField::where('form_type', $tab)->orderBy('sort_order')->get();
        $terms   = LegalPage::terms();
        $privacy = LegalPage::privacy();

        return view('admin.form_fields.index', compact('fields', 'tab', 'terms', 'privacy'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'form_type'   => 'required|in:application,report',
            'label'       => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'type'        => 'required|in:text,textarea,date,radio,checkbox,select,tel,email,number,image,campaign_thumbnail,campaign_description,campaign_requirements,campaign_notes,campaign_initial_fee,campaign_recurring_fee,campaign_cooperation_fee,application_available_times,application_wants_continuation',
            'options'     => 'nullable|string',
        ]);

        $options = null;
        if ($request->options) {
            $lines   = array_filter(array_map('trim', explode("\n", $request->options)));
            $options = array_values(array_map(fn($l) => ['value' => $l, 'label' => $l], $lines));
        }

        FormField::create([
            'form_type'   => $request->form_type,
            'field_key'   => FormField::generateKey(),
            'label'       => $request->label,
            'description' => $request->description,
            'type'        => $request->type,
            'is_required' => $request->boolean('is_required'),
            'is_visible'  => $request->boolean('is_visible', true),
            'options'     => $options,
            'sort_order'  => FormField::where('form_type', $request->form_type)->max('sort_order') + 1,
            'is_system'   => false,
        ]);

        return back()->with('success', 'フォーム項目を追加しました。')->withFragment('tab-' . $request->form_type);
    }

    public function update(Request $request, FormField $formField): RedirectResponse
    {
        $request->validate([
            'label'       => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_required' => 'nullable|boolean',
            'is_visible'  => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
            'options'     => 'nullable|string',
        ]);

        $data = [
            'label'       => $request->label,
            'description' => $request->description,
            'is_required' => $request->boolean('is_required'),
            'is_visible'  => $request->boolean('is_visible', true),
            'sort_order'  => $request->integer('sort_order', $formField->sort_order),
        ];

        if (!$formField->is_system && $request->filled('options')) {
            $lines = array_filter(array_map('trim', explode("\n", $request->options)));
            $data['options'] = array_values(array_map(fn($l) => ['value' => $l, 'label' => $l], $lines));
        }

        $formField->update($data);

        return back()->with('success', '更新しました。');
    }

    public function destroy(FormField $formField): RedirectResponse
    {
        if ($formField->is_system) {
            return back()->with('error', 'システム項目は削除できません。');
        }
        $formField->delete();
        return back()->with('success', '削除しました。');
    }

    public function toggle(Request $request, FormField $formField): RedirectResponse
    {
        $field   = $request->input('field');
        $allowed = ['is_required', 'is_visible'];
        if (!in_array($field, $allowed)) abort(422);
        $formField->update([$field => !$formField->$field]);
        return back();
    }

    public function updateLegal(Request $request, string $slug): RedirectResponse
    {
        $request->validate([
            'title'   => 'required|string|max:100',
            'content' => 'nullable|string',
        ]);

        LegalPage::where('slug', $slug)->update([
            'title'   => $request->title,
            'content' => $request->content,
        ]);

        return back()->with('success', '更新しました。');
    }
}
