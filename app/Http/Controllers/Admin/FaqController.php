<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(Request $request): View
    {
        // カテゴリの表示順は、そのカテゴリ内で最も小さいsort_orderを基準にする
        $categories = Faq::selectRaw('category, MIN(sort_order) as min_order')
            ->groupBy('category')
            ->orderBy('min_order')
            ->pluck('category');

        $category = $request->input('category', $categories->first());

        $faqs = Faq::where('category', $category)->orderBy('sort_order')->get();

        return view('admin.faqs.index', compact('categories', 'category', 'faqs'));
    }

    public function create(): View
    {
        $categories = Faq::select('category')->distinct()->orderBy('category')->pluck('category');
        return view('admin.faqs.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['sort_order'] = Faq::where('category', $validated['category'])->max('sort_order') + 1;

        Faq::create($validated);

        return redirect()->route('admin.faqs.index', ['category' => $validated['category']])
            ->with('success', 'FAQを追加しました。');
    }

    public function edit(Faq $faq): View
    {
        $categories = Faq::select('category')->distinct()->orderBy('category')->pluck('category');
        return view('admin.faqs.edit', compact('faq', 'categories'));
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $faq->update($validated);

        return redirect()->route('admin.faqs.index', ['category' => $faq->category])
            ->with('success', 'FAQを更新しました。');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $category = $faq->category;
        $faq->delete();

        return redirect()->route('admin.faqs.index', ['category' => $category])
            ->with('success', 'FAQを削除しました。');
    }

    public function toggleVisible(Faq $faq): RedirectResponse
    {
        $faq->update(['is_visible' => !$faq->is_visible]);
        return back()->with('success', '表示設定を変更しました。');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        foreach ($validated['ids'] as $order => $id) {
            Faq::where('id', $id)->update(['sort_order' => $order]);
        }

        return response()->json(['success' => true]);
    }

    private function rules(): array
    {
        return [
            'category' => 'required|string|max:50',
            'question' => 'required|string',
            'answer'   => 'required|string',
        ];
    }
}
