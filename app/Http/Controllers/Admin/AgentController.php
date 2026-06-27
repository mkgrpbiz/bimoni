<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentReferralCode;
use App\Models\Application;
use App\Models\MonitorReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(): View
    {
        $agents = Agent::with(['children.codes', 'codes'])->whereNull('parent_id')->latest()->get();

        // 全コード → agentId のマッピングを構築
        $codeToAgent = [];
        foreach ($agents as $agent) {
            foreach ($agent->getAllCodeStrings() as $code) {
                $codeToAgent[$code] = $agent->id;
            }
        }
        $allCodes = array_keys($codeToAgent);

        // 登録数・応募数・報告数を一括取得
        $usersByCode   = User::whereIn('referred_by_code', $allCodes)
            ->selectRaw('referred_by_code, count(*) as cnt, GROUP_CONCAT(id) as user_ids')
            ->groupBy('referred_by_code')->get();

        $registeredMap = []; // agentId => count
        $userIdsByAgent = []; // agentId => [user_id, ...]
        foreach ($usersByCode as $row) {
            $aId = $codeToAgent[$row->referred_by_code] ?? null;
            if (!$aId) continue;
            $registeredMap[$aId] = ($registeredMap[$aId] ?? 0) + $row->cnt;
            foreach (explode(',', $row->user_ids) as $uid) {
                $userIdsByAgent[$aId][] = (int) $uid;
            }
        }

        $appMap    = [];
        $reportMap = [];
        foreach ($userIdsByAgent as $aId => $uids) {
            $appMap[$aId]    = Application::whereIn('user_id', $uids)->count();
            $reportMap[$aId] = MonitorReport::whereIn('user_id', $uids)->where('status', 'approved')->count();
        }

        return view('admin.agents.index', compact('agents', 'registeredMap', 'appMap', 'reportMap'));
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

    public function deleteCode(AgentReferralCode $code): RedirectResponse
    {
        // 紐づいているユーザーがいる場合は削除不可
        $userCount = \App\Models\User::where('referred_by_code', $code->code)->count();
        if ($userCount > 0) {
            return back()->with('error', "このコードには{$userCount}名のユーザーが紐づいているため削除できません。");
        }

        $agentId = $code->agent_id;
        $code->delete();

        return back()->with('success', 'コードを削除しました。');
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $agent->delete();
        return redirect()->route('admin.agents.index')->with('success', '削除しました。');
    }
}
