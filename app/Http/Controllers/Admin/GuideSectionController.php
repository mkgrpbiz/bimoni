<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuidePage;
use App\Models\GuideSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideSectionController extends Controller
{
    public function store(Request $request, GuidePage $guidePage): RedirectResponse
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'intro_text' => 'nullable|string',
        ]);
        $validated['guide_page_id'] = $guidePage->id;
        $validated['sort_order'] = GuideSection::where('guide_page_id', $guidePage->id)->max('sort_order') + 1;

        GuideSection::create($validated);

        return redirect()->route('admin.guide_pages.edit', $guidePage)->with('success', 'セクションを追加しました。');
    }

    public function edit(GuideSection $guideSection): View
    {
        $guideSection->load(['notes', 'steps', 'page']);
        return view('admin.guide_sections.edit', ['section' => $guideSection]);
    }

    public function update(Request $request, GuideSection $guideSection): RedirectResponse
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:255',
            'intro_text' => 'nullable|string',
        ]);
        $guideSection->update($validated);

        return redirect()->route('admin.guide_sections.edit', $guideSection)->with('success', 'セクションを更新しました。');
    }

    public function destroy(GuideSection $guideSection): RedirectResponse
    {
        $page = $guideSection->page;
        $guideSection->delete();

        return redirect()->route('admin.guide_pages.edit', $page)->with('success', 'セクションを削除しました。');
    }

    public function toggleVisible(GuideSection $guideSection): RedirectResponse
    {
        $guideSection->update(['is_visible' => !$guideSection->is_visible]);
        return back()->with('success', '表示設定を変更しました。');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        foreach ($validated['ids'] as $order => $id) {
            GuideSection::where('id', $id)->update(['sort_order' => $order]);
        }
        return response()->json(['success' => true]);
    }
}
