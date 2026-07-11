<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuideSection;
use App\Models\GuideStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuideStepController extends Controller
{
    public function store(Request $request, GuideSection $guideSection): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('guides/steps', 'public');
        }
        $validated['guide_section_id'] = $guideSection->id;
        $validated['sort_order'] = GuideStep::where('guide_section_id', $guideSection->id)->max('sort_order') + 1;

        GuideStep::create($validated);

        return redirect()->route('admin.guide_sections.edit', $guideSection)->with('success', 'ステップを追加しました。');
    }

    public function update(Request $request, GuideStep $guideStep): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        if ($request->hasFile('image')) {
            if ($guideStep->image) {
                Storage::disk('public')->delete($guideStep->image);
            }
            $validated['image'] = $request->file('image')->store('guides/steps', 'public');
        }

        $guideStep->update($validated);

        return redirect()->route('admin.guide_sections.edit', $guideStep->guide_section_id)->with('success', 'ステップを更新しました。');
    }

    public function destroy(GuideStep $guideStep): RedirectResponse
    {
        $sectionId = $guideStep->guide_section_id;
        if ($guideStep->image) {
            Storage::disk('public')->delete($guideStep->image);
        }
        $guideStep->delete();

        return redirect()->route('admin.guide_sections.edit', $sectionId)->with('success', 'ステップを削除しました。');
    }

    public function toggleVisible(GuideStep $guideStep): RedirectResponse
    {
        $guideStep->update(['is_visible' => !$guideStep->is_visible]);
        return back()->with('success', '表示設定を変更しました。');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        foreach ($validated['ids'] as $order => $id) {
            GuideStep::where('id', $id)->update(['sort_order' => $order]);
        }
        return response()->json(['success' => true]);
    }

    private function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'sub_text'    => 'nullable|string|max:255',
            'image'       => 'nullable|image|max:5120',
        ];
    }
}
