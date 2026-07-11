<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\GuidePage;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function show(GuidePage $guidePage): View
    {
        abort_unless($guidePage->is_visible, 404);

        $guidePage->load(['sections' => function ($q) {
            $q->where('is_visible', true);
        }, 'sections.notes', 'sections.steps' => function ($q) {
            $q->where('is_visible', true);
        }]);

        return view('member.guide.show', ['page' => $guidePage]);
    }
}
