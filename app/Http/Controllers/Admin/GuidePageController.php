<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuidePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GuidePageController extends Controller
{
    public function index(): View
    {
        $pages = GuidePage::orderBy('sort_order')->get();
        return view('admin.guide_pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.guide_pages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));

        if ($request->hasFile('hero_image')) {
            $validated['hero_image'] = $request->file('hero_image')->store('guides', 'public');
        }
        $validated['sort_order'] = GuidePage::max('sort_order') + 1;

        $page = GuidePage::create($validated);

        return redirect()->route('admin.guide_pages.edit', $page)->with('success', 'ページを作成しました。');
    }

    public function edit(GuidePage $guidePage): View
    {
        $guidePage->load(['sections.notes', 'sections.steps']);
        return view('admin.guide_pages.edit', ['page' => $guidePage]);
    }

    public function update(Request $request, GuidePage $guidePage): RedirectResponse
    {
        $validated = $request->validate($this->rules($request, $guidePage));

        if ($request->hasFile('hero_image')) {
            if ($guidePage->hero_image) {
                Storage::disk('public')->delete($guidePage->hero_image);
            }
            $validated['hero_image'] = $request->file('hero_image')->store('guides', 'public');
        }

        $guidePage->update($validated);

        return redirect()->route('admin.guide_pages.edit', $guidePage)->with('success', 'ページを更新しました。');
    }

    public function destroy(GuidePage $guidePage): RedirectResponse
    {
        if ($guidePage->hero_image) {
            Storage::disk('public')->delete($guidePage->hero_image);
        }
        $guidePage->delete();

        return redirect()->route('admin.guide_pages.index')->with('success', 'ページを削除しました。');
    }

    public function toggleVisible(GuidePage $guidePage): RedirectResponse
    {
        $guidePage->update(['is_visible' => !$guidePage->is_visible]);
        return back()->with('success', '表示設定を変更しました。');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        foreach ($validated['ids'] as $order => $id) {
            GuidePage::where('id', $id)->update(['sort_order' => $order]);
        }
        return response()->json(['success' => true]);
    }

    private function rules(Request $request, ?GuidePage $guidePage = null): array
    {
        return [
            'slug'       => 'required|alpha_dash|max:100|unique:guide_pages,slug,' . ($guidePage?->id ?? 'NULL'),
            'title'      => 'required|string|max:255',
            'hero_image' => 'nullable|image|max:5120',
            'cta_label'  => 'nullable|string|max:100',
            'cta_url'    => 'nullable|url|max:500',
        ];
    }
}
