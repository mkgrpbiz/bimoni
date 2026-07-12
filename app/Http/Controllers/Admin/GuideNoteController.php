<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuideNote;
use App\Models\GuideSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuideNoteController extends Controller
{
    public function store(Request $request, GuideSection $guideSection): RedirectResponse
    {
        $validated = $this->mapped($request->validate($this->rules()));
        $validated['guide_section_id'] = $guideSection->id;
        $validated['sort_order'] = GuideNote::where('guide_section_id', $guideSection->id)->max('sort_order') + 1;

        GuideNote::create($validated);

        return redirect()->route('admin.guide_sections.edit', $guideSection)->with('success', '注意書きを追加しました。');
    }

    public function update(Request $request, GuideNote $guideNote): RedirectResponse
    {
        $validated = $this->mapped($request->validate($this->rules()));
        $guideNote->update($validated);

        return redirect()->route('admin.guide_sections.edit', $guideNote->guide_section_id)->with('success', '注意書きを更新しました。');
    }

    public function destroy(GuideNote $guideNote): RedirectResponse
    {
        $sectionId = $guideNote->guide_section_id;
        $guideNote->delete();

        return redirect()->route('admin.guide_sections.edit', $sectionId)->with('success', '注意書きを削除しました。');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        foreach ($validated['ids'] as $order => $id) {
            GuideNote::where('id', $id)->update(['sort_order' => $order]);
        }
        return response()->json(['success' => true]);
    }

    private function rules(): array
    {
        return [
            'heading'    => 'nullable|string|max:100',
            'body'       => 'required|string',
            'note_style' => 'required|in:pink,orange,red',
        ];
    }

    // フォームのname="style"はJSでform.style（CSSStyleDeclaration）を上書きしてしまうため
    // 入力側はnote_styleで受け取り、DBカラム名のstyleにマッピングする
    private function mapped(array $validated): array
    {
        $validated['style'] = $validated['note_style'];
        unset($validated['note_style']);
        return $validated;
    }
}
