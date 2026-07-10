<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManualAdditionController extends Controller
{
    public function index(Request $request): View
    {
        $users = collect();

        if ($request->filled('q')) {
            $q = $request->q;
            $users = User::where(function ($query) use ($q) {
                $query->where('bimoni_user_id', 'like', "%{$q}%")
                      ->orWhere('line_display_name', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%")
                      ->orWhere('name_kana', 'like', "%{$q}%");
            })->orderBy('name')->limit(30)->get();
        }

        $campaigns = Campaign::orderBy('sort_order')->orderBy('id')->get(['id', 'title', 'status']);

        return view('admin.manual_addition.index', [
            'users'     => $users,
            'campaigns' => $campaigns,
            'q'         => $request->q,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id'     => 'required|exists:users,id',
            'campaign_id' => 'required|exists:campaigns,id',
            'outcome'     => 'required|in:continuation_ok,continuation_ng',
            'date'        => 'nullable|date',
        ]);

        $date = $request->filled('date') ? \Carbon\Carbon::parse($request->date) : now();

        Application::create([
            'user_id'                   => $request->user_id,
            'campaign_id'               => $request->campaign_id,
            'status'                    => 'completed',
            'applied_at'                => $date,
            'completed_at'              => $date,
            'continuation_wish'         => '希望',
            'continuation_response'     => $request->outcome === 'continuation_ok' ? 'possible' : 'not_possible',
            'continuation_responded_at' => $date,
        ]);

        return back()->with('success', '成果を追加しました。')->withInput(['q' => $request->q]);
    }
}
