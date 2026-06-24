<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentReferralCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(): View
    {
        $agents = Agent::with(['children', 'codes'])->whereNull('parent_id')->latest()->get();
        return view('admin.agents.index', compact('agents'));
    }

    public function create(): View
    {
        return view('admin.agents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $agent = Agent::create(['name' => $request->name]);

        // 初期コードを1つ発行
        AgentReferralCode::create(['agent_id' => $agent->id]);

        return redirect()->route('admin.agents.index')->with('success', "{$agent->name} を作成しました。ポータルURL: {$agent->portalUrl()}");
    }

    public function show(Agent $agent): View
    {
        $agent->load(['children.codes', 'codes']);
        return view('admin.agents.show', compact('agent'));
    }

    public function addCode(Agent $agent): RedirectResponse
    {
        AgentReferralCode::create(['agent_id' => $agent->id]);
        return back()->with('success', '紹介コードを追加しました。');
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $agent->delete();
        return redirect()->route('admin.agents.index')->with('success', '削除しました。');
    }
}
