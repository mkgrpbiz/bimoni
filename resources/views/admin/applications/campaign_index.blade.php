@extends('layouts.admin')

@section('title', $campaign->title . ' 応募者一覧')

@section('content')
<div class="flex items-center justify-between gap-3 mb-4">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">応募管理</h1>
    <a href="{{ route('admin.campaigns.daily_slots.index', $campaign) }}"
       class="text-xs bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700">
        日別件数管理
    </a>
</div>

{{-- 案件タブ --}}
@include('admin.applications._campaign_tabs', ['allCampaigns' => $allCampaigns, 'activeCampaignId' => $campaign->id])

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-4 py-2 rounded mb-4 text-sm">{{ session('error') }}</div>
@endif

{{-- ヘッダー集計 --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
    @foreach([
        ['label'=>'当日', 'slot'=>$summary['today']],
        ['label'=>'翌日', 'slot'=>$summary['tomorrow']],
        ['label'=>'翌々日', 'slot'=>$summary['day_after']],
    ] as $day)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 text-sm">
        <div class="font-bold text-gray-700 dark:text-gray-200 mb-1">{{ $day['label'] }}
            @if($day['slot'])<span class="text-xs text-gray-400 ml-1">({{ $day['slot']->target_date->format('m/d') }})</span>@endif
        </div>
        @if($day['slot'])
        <div class="grid grid-cols-4 gap-1 text-center text-xs">
            <div><div class="text-gray-400">目標</div><div class="font-bold text-gray-800 dark:text-gray-100">{{ $day['slot']->planned_count }}</div></div>
            <div><div class="text-gray-400">打診</div><div class="font-bold text-purple-600">{{ $day['slot']->invited_count }}</div></div>
            <div><div class="text-gray-400">予約</div><div class="font-bold text-indigo-600">{{ $day['slot']->reserved_count }}</div></div>
            <div><div class="text-gray-400">完了</div><div class="font-bold text-teal-600">{{ $day['slot']->completed_count }}</div></div>
        </div>
        @else
        <div class="text-gray-400 text-xs">予定未登録</div>
        @endif
    </div>
    @endforeach
</div>

{{-- 実施完了サマリー --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-4 text-sm flex gap-6 items-center">
    <div>
        <span class="text-gray-500">目標男女比: </span>
        <span class="font-bold">{{ $summary['target_male_ratio'] ?? '-' }}%男 / {{ $summary['target_female_ratio'] ?? '-' }}%女</span>
    </div>
    <div>
        <span class="text-gray-500">実施完了: </span>
        <span class="font-bold text-teal-600">{{ $summary['total_completed'] }}件</span>
        （男 {{ $summary['completed_male'] }} / 女 {{ $summary['completed_female'] }}）
    </div>
</div>

{{-- フィルター --}}
<form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-4 flex gap-3 items-end flex-wrap">
    <div>
        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ステータス</label>
        <select name="status" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
            <option value="">すべて</option>
            @foreach([
                'pending'       => '応募',
                'line_contacted'=> '打診中',
                'scheduled'     => '予約中',
                'confirming'    => '実施確認中',
                'completed'     => '実施完了',
                'reported'      => '報告済',
                'approved'      => '承認済',
                'cancelled'     => 'キャンセル',
            ] as $val => $label)
                <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">名前検索</label>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="名前・フリガナ"
               class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm w-40">
    </div>
    <button type="submit" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">絞り込み</button>
    <a href="{{ route('admin.campaigns.applications', $campaign) }}" class="text-xs text-gray-400 hover:underline">リセット</a>
</form>

{{-- 応募者テーブル --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-3 py-2 text-left whitespace-nowrap">応募日</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">回答者ID</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">名前</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">フリガナ</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">年齢</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">性別</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">継続</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">実施可能時間</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">ステータス</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">案内予定日時</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">打診回答</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">LINE送信</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">打診URL</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">他案件状況</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">48h制限</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($applications as $app)
            @php
                $user = $app->user;
                $age = $user?->birthdate ? \Carbon\Carbon::parse($user->birthdate)->age : '-';
                $genderLabel = match($user?->gender) { 'male'=>'男', 'female'=>'女', default=>'-' };
                $others = $app->other_applications ?? collect();
                $unlockAt = $app->unlock_at;
                $isLocked = $app->is_locked;
                $lineJobs = $app->lineMessageJobs ?? collect();
                $proposalJob = $lineJobs->where('send_type','proposal')->sortByDesc('created_at')->first();
                $guideJob    = $lineJobs->where('send_type','monitor_guide')->where('status','pending')->first()
                            ?? $lineJobs->where('send_type','monitor_guide')->sortByDesc('created_at')->first();
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 {{ $isLocked ? 'opacity-70' : '' }}">
                <td class="px-3 py-2 whitespace-nowrap text-gray-500">{{ $app->applied_at->format('m/d') }}</td>
                <td class="px-3 py-2 text-gray-400">{{ $user?->erme_respondent_id ?? '-' }}</td>
                <td class="px-3 py-2 font-medium whitespace-nowrap">{{ $user?->name ?? '（未登録）' }}</td>
                <td class="px-3 py-2 text-gray-500">{{ $user?->name_kana ?? '-' }}</td>
                <td class="px-3 py-2 text-center">{{ $age }}</td>
                <td class="px-3 py-2 text-center">{{ $genderLabel }}</td>
                <td class="px-3 py-2 text-center">
                    @if($user?->wants_continuation)
                        <span class="text-green-600">○</span>
                    @else
                        <span class="text-gray-300">-</span>
                    @endif
                </td>
                <td class="px-3 py-2 text-gray-500">
                    @if($user?->available_times)
                        {{ implode('・', $user->available_times) }}
                    @else
                        -
                    @endif
                </td>
                <td class="px-3 py-2 whitespace-nowrap">
                    <span class="px-1.5 py-0.5 rounded text-xs {{ $app->getStatusColor() }}">
                        {{ $app->getStatusLabel() }}
                    </span>
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-gray-500">
                    {{ $app->invited_at?->format('m/d H:i') ?? '-' }}
                    @if($app->invited_end_at)
                        <span class="text-gray-300">〜{{ $app->invited_end_at->format('H:i') }}</span>
                    @endif
                </td>
                {{-- 打診回答 --}}
                <td class="px-3 py-2 whitespace-nowrap">
                    @if($app->proposal_answer === 'yes')
                        <span class="text-green-600 font-bold">はい</span>
                        @if($app->proposal_answered_at)
                            <div class="text-gray-400 text-xs">{{ $app->proposal_answered_at->format('m/d H:i') }}</div>
                        @endif
                    @elseif($app->proposal_answer === 'no')
                        <span class="text-red-500">いいえ</span>
                    @elseif($app->status === 'line_contacted')
                        <span class="text-yellow-500 text-xs">未回答</span>
                    @else
                        <span class="text-gray-300 text-xs">-</span>
                    @endif
                </td>
                {{-- LINE送信状態 --}}
                <td class="px-3 py-2 whitespace-nowrap">
                    @if($proposalJob)
                        <div>
                            <span class="px-1 py-0.5 rounded text-xs {{ $proposalJob->getStatusColor() }}">
                                打診:{{ $proposalJob->getStatusLabel() }}
                            </span>
                        </div>
                    @endif
                    @if($guideJob)
                        <div class="mt-0.5">
                            <span class="px-1 py-0.5 rounded text-xs {{ $guideJob->getStatusColor() }}">
                                案内:{{ $guideJob->getStatusLabel() }}
                                @if($guideJob->status === 'pending') ({{ $guideJob->send_at->format('m/d H:i') }}) @endif
                            </span>
                        </div>
                    @endif
                    @if(!$proposalJob && !$guideJob)
                        <span class="text-gray-300 text-xs">-</span>
                    @endif
                </td>
                {{-- 打診URL --}}
                <td class="px-3 py-2 whitespace-nowrap">
                    @if($app->proposal_token)
                        <div class="flex gap-1">
                            <a href="{{ route('proposals.confirm', $app->proposal_token) }}" target="_blank"
                               class="text-xs text-blue-500 hover:underline">開く</a>
                            <button onclick="copyUrl('{{ route('proposals.confirm', $app->proposal_token) }}')"
                                    class="text-xs text-gray-400 hover:text-gray-600">コピー</button>
                        </div>
                    @else
                        <span class="text-gray-300 text-xs">未生成</span>
                    @endif
                </td>
                {{-- 他案件状況 --}}
                <td class="px-3 py-2">
                    @if($others->isEmpty())
                        <span class="text-gray-300">-</span>
                    @else
                        @foreach($others as $other)
                            @php
                                $otherLabel = match($other->status) {
                                    'line_contacted' => '打診中',
                                    'scheduled'      => '予約中',
                                    'confirming'     => '実施確認中',
                                    'completed'      => '実施完了',
                                    default          => $other->getStatusLabel(),
                                };
                            @endphp
                            <div class="text-orange-600 whitespace-nowrap">
                                他案件で{{ $otherLabel }}（{{ $other->campaign?->title ?? '不明' }}）
                            </div>
                        @endforeach
                    @endif
                </td>
                {{-- 48時間制限 --}}
                <td class="px-3 py-2 whitespace-nowrap">
                    @if($unlockAt)
                        <span class="text-red-500 text-xs">
                            {{ $unlockAt->format('m/d H:i') }}〜打診可能
                        </span>
                    @else
                        <span class="text-gray-300 text-xs">制限なし</span>
                    @endif
                </td>
                {{-- 操作 --}}
                <td class="px-3 py-2 whitespace-nowrap">
                    <div class="flex gap-1 flex-wrap">
                        @if($app->status === 'pending' && !$isLocked)
                        <button type="button"
                                onclick="openProposalModal({{ $app->id }}, '{{ addslashes($user?->name ?? '') }}', '{{ route('admin.applications.status', $app) }}')"
                                class="bg-purple-600 text-white px-1.5 py-0.5 rounded hover:bg-purple-700 text-xs">
                            打診
                        </button>
                        @endif
                        @if($app->status === 'line_contacted')
                        <form method="POST" action="{{ route('admin.applications.status', $app) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="scheduled">
                            <button type="submit" class="bg-indigo-600 text-white px-1.5 py-0.5 rounded hover:bg-indigo-700 text-xs">予約</button>
                        </form>
                        @endif
                        @if($app->status === 'scheduled')
                        <form method="POST" action="{{ route('admin.applications.status', $app) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="confirming">
                            <button type="submit" class="bg-orange-500 text-white px-1.5 py-0.5 rounded hover:bg-orange-600 text-xs">実施確認</button>
                        </form>
                        @endif
                        @if($app->status === 'confirming')
                        <form method="POST" action="{{ route('admin.applications.status', $app) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="bg-teal-600 text-white px-1.5 py-0.5 rounded hover:bg-teal-700 text-xs">完了</button>
                        </form>
                        @endif
                        @if(!in_array($app->status, ['cancelled','point_granted','approved']))
                        <form method="POST" action="{{ route('admin.applications.status', $app) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <button type="submit" class="bg-gray-400 text-white px-1.5 py-0.5 rounded hover:bg-gray-500 text-xs"
                                    onclick="return confirm('キャンセルしますか？')">取消</button>
                        </form>
                        @endif
                        <a href="{{ route('admin.applications.show', $app) }}" class="text-pink-600 dark:text-pink-400 hover:underline text-xs">詳細</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="18" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">応募がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $applications->links() }}</div>

{{-- 打診モーダル --}}
<div id="proposalModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-96">
        <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-1">打診送信</h3>
        <p id="proposalUserName" class="text-sm text-gray-500 mb-4"></p>
        <form id="proposalForm" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="line_contacted">
            <div>
                <label class="block text-xs text-gray-500 mb-1">案内予定日時 <span class="text-red-400">*</span></label>
                <input type="datetime-local" name="invited_at" required
                       class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
                <p class="text-xs text-gray-400 mt-0.5">ユーザーに表示・LINEに記載されます</p>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">案内終了日時</label>
                <input type="datetime-local" name="invited_end_at"
                       class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">メモ</label>
                <input type="text" name="memo"
                       class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
            </div>
            <div class="flex gap-2 pt-1">
                <button type="submit"
                        class="flex-1 bg-purple-600 text-white py-2 rounded text-sm hover:bg-purple-700 font-medium">
                    打診送信
                </button>
                <button type="button" onclick="closeProposalModal()"
                        class="flex-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 py-2 rounded text-sm">
                    キャンセル
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openProposalModal(appId, userName, actionUrl) {
    document.getElementById('proposalUserName').textContent = '対象: ' + userName;
    document.getElementById('proposalForm').action = actionUrl;
    document.getElementById('proposalModal').classList.remove('hidden');
}
function closeProposalModal() {
    document.getElementById('proposalModal').classList.add('hidden');
}
function copyUrl(url) {
    navigator.clipboard.writeText(url).then(function() {
        alert('URLをコピーしました');
    });
}
</script>
@endsection
