<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChildController extends Controller
{
    public function index()
    {
        $agent = \App\Services\PortalService::agent();
        if ($agent->parent_id) abort(403);
        $agent->load('children.codes');
        return view('portal.children.index', compact('agent'));
    }

    public function create()
    {
        $agent = \App\Services\PortalService::agent();
        if ($agent->parent_id) abort(403);
        return view('portal.children.create', compact('agent'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $agent = \App\Services\PortalService::agent();
        if ($agent->parent_id) abort(403);

        $request->validate([
            'name'              => 'required|string|max:100',
            'child_reward_500'  => 'required|integer|min:0|max:500',
            'child_reward_1000' => 'required|integer|min:0|max:1000',
        ]);

        $child = \App\Models\Agent::create([
            'parent_id'          => $agent->id,
            'name'               => $request->name,
            'child_reward_500'   => $request->child_reward_500,
            'child_reward_1000'  => $request->child_reward_1000,
        ]);

        \App\Models\AgentReferralCode::create(['agent_id' => $child->id]);

        return redirect()->route('portal.children')->with('success', "{$child->name} を作成しました。");
    }

    public function addCode(\App\Models\Agent $child)
    {
        $agent = \App\Services\PortalService::agent();
        if ($child->parent_id !== $agent->id) abort(403);

        \App\Models\AgentReferralCode::create(['agent_id' => $child->id]);
        return back()->with('success', 'コードを追加しました。');
    }
}
