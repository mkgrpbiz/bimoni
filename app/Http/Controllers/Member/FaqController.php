<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        $categories = Faq::where('is_visible', true)
            ->selectRaw('category, MIN(sort_order) as min_order')
            ->groupBy('category')
            ->orderBy('min_order')
            ->pluck('category');

        $faqsByCategory = Faq::where('is_visible', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return view('member.faq.index', compact('categories', 'faqsByCategory'));
    }
}
