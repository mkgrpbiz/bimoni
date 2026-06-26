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
            'code' => 'nullable|string|max:20|unique:agent_referral_codes,code',
        ]);

        $agent = Agent::create(['name' => $request->name]);

        $codeData = ['agent_id' => $agent->id, 'label' => $request->label ?? null];
        if ($request->filled('code')) {
            $codeData['code'] = strtoupper($request->code);
        }
        AgentReferralCode::create($codeData);

        return redirect()->route('admin.agents.index')->with('success', "{$agent->name} を作成しました。ポータルURL: {$agent->portalUrl()}");
    }

    public function show(Agent $agent): View
    {
        $agent->load(['children.codes', 'codes']);
        return view('admin.agents.show', compact('agent'));
    }

    public function addCode(Agent $agent, Request $request): RedirectResponse
    {
        $request->validate([
            'code'  => 'nullable|string|max:20|unique:agent_referral_codes,code',
            'label' => 'nullable|string|max:100',
        ]);

        $data = ['agent_id' => $agent->id, 'label' => $request->label ?? null];
        if ($request->filled('code')) {
            $data['code'] = strtoupper($request->code);
        }
        AgentReferralCode::create($data);

        return back()->with('success', '紹介コードを追加しました。');
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $agent->delete();
        return redirect()->route('admin.agents.index')->with('success', '削除しました。');
    }
}
