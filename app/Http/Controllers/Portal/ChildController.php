<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentReferralCode;
use App\Models\User;
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

    public function store(Request $request)
    {
        $agent = \App\Services\PortalService::agent();
        if ($agent->parent_id) abort(403);

        $request->validate([
            'name'              => 'required|string|max:100',
            'child_reward_500'  => 'required|integer|min:0|max:500',
            'child_reward_1000' => 'required|integer|min:0|max:1000',
            'code'              => 'nullable|string|max:20|unique:agent_referral_codes,code',
        ]);

        $child = Agent::create([
            'parent_id'          => $agent->id,
            'name'               => $request->name,
            'child_reward_500'   => $request->child_reward_500,
            'child_reward_1000'  => $request->child_reward_1000,
        ]);

        $codeData = ['agent_id' => $child->id];
        if ($request->filled('code')) {
            $codeData['code'] = strtoupper($request->code);
        }
        AgentReferralCode::create($codeData);

        return redirect()->route('portal.children')->with('success', "{$child->name} を作成しました。");
    }

    public function updateReward(Request $request, Agent $child)
    {
        $agent = \App\Services\PortalService::agent();
        if ($child->parent_id !== $agent->id) abort(403);

        $request->validate([
            'name'              => 'required|string|max:100',
            'child_reward_500'  => 'required|integer|min:0|max:500',
            'child_reward_1000' => 'required|integer|min:0|max:1000',
        ]);

        $child->update([
            'name'              => $request->name,
            'child_reward_500'  => $request->child_reward_500,
            'child_reward_1000' => $request->child_reward_1000,
        ]);

        return back()->with('success', "{$child->name} の支払い額を変更しました。");
    }

    public function addCode(Request $request, Agent $child)
    {
        $agent = \App\Services\PortalService::agent();
        if ($child->parent_id !== $agent->id) abort(403);

        $request->validate([
            'code' => 'nullable|string|max:20|unique:agent_referral_codes,code',
        ]);

        $codeData = ['agent_id' => $child->id];
        if ($request->filled('code')) {
            $codeData['code'] = strtoupper($request->code);
        }
        AgentReferralCode::create($codeData);

        return back()->with('success', 'コードを追加しました。');
    }

    public function deleteCode(Request $request, AgentReferralCode $code)
    {
        $agent = \App\Services\PortalService::agent();
        $child = $code->agent;
        if ($child->parent_id !== $agent->id) abort(403);

        if (User::where('referred_by_code', $code->code)->exists()) {
            return back()->withErrors(['error' => 'このコードには登録者がいるため削除できません。']);
        }

        $code->delete();
        return back()->with('success', "コード {$code->code} を削除しました。");
    }
}
