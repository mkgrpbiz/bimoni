<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LineMessageDefault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LineMessageDefaultController extends Controller
{
    private const PR_MEDIA_LIST = [
        'AD'      => 'AD',
        'IF'      => 'IF',
        'LINE'    => 'LINE',
        'monitor' => 'モニター',
    ];

    public function index(): View
    {
        $defaults = LineMessageDefault::all()->keyBy('pr_media');
        return view('admin.line_message_defaults.index', [
            'prMediaList' => self::PR_MEDIA_LIST,
            'defaults'    => $defaults,
        ]);
    }

    public function update(Request $request, string $prMedia): RedirectResponse
    {
        if (!array_key_exists($prMedia, self::PR_MEDIA_LIST)) {
            abort(404);
        }

        $validated = $request->validate([
            'monitor_invite_message' => 'nullable|string',
            'monitor_end_message'    => 'nullable|string',
        ]);

        LineMessageDefault::updateOrCreate(
            ['pr_media' => $prMedia],
            $validated
        );

        return back()->with('success', self::PR_MEDIA_LIST[$prMedia] . ' のデフォルトを保存しました。');
    }
}
