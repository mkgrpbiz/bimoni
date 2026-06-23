<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormFieldController extends Controller
{
    public function index(): View
    {
        $fields = FormField::orderBy('sort_order')->get();

        return view('admin.form_fields.index', compact('fields'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'field_key' => 'required|string|alpha_dash|unique:form_fields,field_key',
            'label'     => 'required|string|max:100',
            'type'      => 'required|in:text,textarea,date,radio,checkbox,select,tel,email,number',
            'options'   => 'nullable|string',
        ]);

        $options = null;
        if ($request->options) {
            $lines   = array_filter(array_map('trim', explode("\n", $request->options)));
            $options = array_values(array_map(fn($l) => ['value' => $l, 'label' => $l], $lines));
        }

        FormField::create([
            'field_key'   => $request->field_key,
            'label'       => $request->label,
            'type'        => $request->type,
            'is_required' => $request->boolean('is_required'),
            'is_visible'  => $request->boolean('is_visible', true),
            'options'     => $options,
            'sort_order'  => FormField::max('sort_order') + 1,
            'is_system'   => false,
        ]);

        return back()->with('success', 'フォーム項目を追加しました。');
    }

    public function update(Request $request, FormField $formField): RedirectResponse
    {
        $request->validate([
            'label'      => 'required|string|max:100',
            'is_required' => 'nullable|boolean',
            'is_visible'  => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
            'options'     => 'nullable|string',
        ]);

        $data = [
            'label'      => $request->label,
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
        $field  = $request->input('field');
        $allowed = ['is_required', 'is_visible'];

        if (!in_array($field, $allowed)) abort(422);

        $formField->update([$field => !$formField->$field]);

        return back();
    }
}
