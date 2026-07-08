@extends('layouts.admin')

@section('title', $campaign->title . ' 応募者一覧')

@section('content')
<div class="flex items-center justify-between gap-3 mb-4">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">応募管理</h1>
    <a href="{{ route('admin.campaigns.daily_slots.index', $campaign) }}"
       class="text-xs bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600">
        日別件数管理
    </a>
</div>

{{-- 案件ステータスタブ --}}
@php
$statusTabs = [
    'published' => ['label' => '公開中',   'color' => 'bg-green-500'],
    'paused'    => ['label' => '一時停止', 'color' => 'bg-orange-500'],
    'closed'    => ['label' => '終了',     'color' => 'bg-gray-500'],
    'draft'     => ['label' => '下書き',   'color' => 'bg-yellow-500'],
];
@endphp
<div class="flex border-b border-gray-200 mb-0">
    @foreach($statusTabs as $key => $t)
    <a href="{{ route('admin.applications.index', ['status' => $key]) }}"
       class="flex items-center gap-1.5 px-5 py-2.5 text-sm font-medium border-b-2 transition-colors
              {{ $campaignStatus === $key
                  ? 'border-pink-500 text-pink-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        {{ $t['label'] }}
        <span class="text-xs font-bold px-1.5 py-0.5 rounded-full text-white {{ $t['color'] }}">
            {{ $tabCounts->get($key, 0) }}
        </span>
    </a>
    @endforeach
</div>

{{-- 案件タブ --}}
@include('admin.applications._campaign_tabs', ['allCampaigns' => $allCampaigns, 'activeCampaignId' => $campaign->id])

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-4 py-2 rounded mb-4 text-sm">{{ session('error') }}</div>
@endif

{{-- アラート --}}
@if($tomorrowUnderAlerts->isNotEmpty() || $continuationRateAlerts->isNotEmpty())
<div class="space-y-2 mb-4">
    @if($tomorrowUnderAlerts->isNotEmpty())
    <div class="bg-yellow-50 border border-yellow-300 rounded-lg px-4 py-2 text-sm text-yellow-800">
        <div class="font-bold mb-1">翌日未達成打診 <span class="font-normal text-xs ml-1">{{ now()->addDay()->format('m/d') }}</span></div>
        <div class="flex flex-wrap gap-2">
            @foreach($tomorrowUnderAlerts as $under)
            <a href="{{ route('admin.campaigns.applications', $under['slot']->campaign_id) }}"
               class="bg-yellow-100 border border-yellow-300 rounded px-2 py-0.5 text-xs font-medium hover:bg-yellow-200">
                {{ $under['slot']->campaign?->title ?? '不明' }}（打診{{ $under['booked'] }}/目標{{ $under['planned'] }}）
            </a>
            @endforeach
        </div>
    </div>
    @endif

    @if($continuationRateAlerts->isNotEmpty())
    <div class="bg-green-50 border border-green-300 rounded-lg px-4 py-2 text-sm text-green-800">
        <div class="font-bold mb-1">未達成目標継続率</div>
        <div class="flex flex-wrap gap-2">
            @foreach($continuationRateAlerts as $alert)
            <a href="{{ route('admin.campaigns.applications', $alert['campaign']->id) }}"
               class="bg-green-100 border border-green-300 rounded px-2 py-0.5 text-xs font-medium hover:bg-green-200">
                {{ $alert['campaign']->title }}（完了{{ $alert['actual'] }}%/目標{{ $alert['target'] }}%）
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
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
            @if($day['slot'])<span class="text-xs text-gray-700 ml-1">({{ $day['slot']->target_date->format('m/d') }})</span>@endif
        </div>
        @if($day['slot'])
        <div class="grid grid-cols-4 gap-1 text-center text-xs">
            <div><div class="text-gray-700">目標</div><div class="font-bold text-gray-800 dark:text-gray-100">{{ $day['slot']->planned_count }}</div></div>
            <div><div class="text-gray-700">打診</div><div class="font-bold text-purple-600">{{ $day['slot']->invited_count }}</div></div>
            <div><div class="text-gray-700">予約</div><div class="font-bold text-indigo-600">{{ $day['slot']->reserved_count }}</div></div>
            <div><div class="text-gray-700">完了</div><div class="font-bold text-teal-600">{{ $day['slot']->completed_count }}</div></div>
        </div>
        @else
        <div class="text-gray-700 text-xs">予定未登録</div>
        @endif
    </div>
    @endforeach
</div>

{{-- 実施完了サマリー --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-4 text-sm flex gap-6 items-center flex-wrap">
    @php
        $totalC      = $summary['total_completed'];
        $maleRatio   = $totalC > 0 ? round($summary['completed_male']   / $totalC * 100) : null;
        $femaleRatio = $totalC > 0 ? round($summary['completed_female'] / $totalC * 100) : null;
        $targetMale   = $summary['target_male_ratio']   ?? null;
        $targetFemale = $summary['target_female_ratio'] ?? null;
        $targetCont   = $campaign->continuation_rate;
        $actualCont   = $totalC > 0 ? round($summary['continuation_ok_count'] / $totalC * 100) : null;
    @endphp
    <div class="flex gap-6 items-center flex-wrap text-sm">
        <div>
            <span class="text-gray-500">実施完了</span>
            <span class="font-bold text-teal-600 ml-1">{{ $totalC }}件</span>
        </div>
        <div>
            <span class="text-gray-500">男性比</span>
            <span class="text-gray-400 text-xs ml-1">目標 {{ $targetMale !== null ? $targetMale.'%' : '-' }}</span>
            <span class="font-bold text-blue-600 ml-1">/ 完了 {{ $maleRatio !== null ? $maleRatio.'%' : '-' }}</span>
            <span class="text-gray-400 text-xs">（{{ $summary['completed_male'] }}件）</span>
        </div>
        <div>
            <span class="text-gray-500">女性比</span>
            <span class="text-gray-400 text-xs ml-1">目標 {{ $targetFemale !== null ? $targetFemale.'%' : '-' }}</span>
            <span class="font-bold text-pink-500 ml-1">/ 完了 {{ $femaleRatio !== null ? $femaleRatio.'%' : '-' }}</span>
            <span class="text-gray-400 text-xs">（{{ $summary['completed_female'] }}件）</span>
        </div>
        <div>
            <span class="text-gray-500">継続率</span>
            <span class="text-gray-400 text-xs ml-1">目標 {{ $targetCont !== null ? $targetCont.'%' : '-' }}</span>
            <span class="font-bold text-green-600 ml-1">/ 完了 {{ $actualCont !== null ? $actualCont.'%' : '-' }}</span>
            <span class="text-gray-400 text-xs">（{{ $summary['continuation_ok_count'] }}件）</span>
        </div>
    </div>
</div>

{{-- フィルター --}}
<form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-4 flex gap-3 items-end flex-wrap">
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">ステータス</label>
        <select name="status" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
            <option value="">すべて</option>
            @foreach([
                'pending'        => '応募',
                'line_contacted' => '打診中',
                'scheduled'      => '予約中',
                'confirming'     => '実施確認中',
                'completed'      => '実施完了',
                'cancelled'      => 'キャンセル',
            ] as $val => $label)
                <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">名前検索</label>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="名前・フリガナ"
               class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm w-40">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.campaigns.applications', $campaign) }}" class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">リセット</a>
</form>

{{-- 応募者テーブル --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-3 py-2 text-left whitespace-nowrap">応募日時</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">LINE表示名</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">名前</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">フリガナ</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">年齢</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">性別</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">継続希望</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">実施可能時間</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">ステータス</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">案内日時</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">打診回答</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">LINE送信</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">他案件状況</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">次回案内可能</th>
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
                $unlinked = str_starts_with($user?->line_user_id ?? '', 'IMPORT_');
            @endphp
            <tr class="{{ $unlinked ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50 dark:hover:bg-gray-750' }} {{ $isLocked ? 'opacity-70' : '' }}">
                <td class="px-3 py-2 whitespace-nowrap text-gray-700">{{ $app->applied_at->format('m/d H:i') }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $user?->line_display_name ?? '-' }}</td>
                <td class="px-3 py-2 font-medium whitespace-nowrap">{{ $user?->name ?? '（未登録）' }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $user?->name_kana ?? '-' }}</td>
                <td class="px-3 py-2 text-center">{{ $age }}</td>
                <td class="px-3 py-2 text-center">{{ $genderLabel }}</td>
                <td class="px-3 py-2 text-center whitespace-nowrap">
                    @if($app->continuation_wish === '希望')
                        <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">希望</span>
                    @elseif($app->continuation_wish === '不可')
                        <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">不可</span>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </td>
                <td class="px-3 py-2 text-gray-700 text-xs">
                    @if($app->purchase_available_times)
                        {{ implode('・', $app->purchase_available_times) }}
                    @elseif($user?->available_times)
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
                <td class="px-3 py-2 whitespace-nowrap text-gray-700">
                    {{ $app->invited_at?->format('m/d H:i') ?? '-' }}
                    @if($app->invited_end_at)
                        <span class="text-gray-800">〜{{ $app->invited_end_at->format('H:i') }}</span>
                    @endif
                </td>
                {{-- 打診回答 --}}
                <td class="px-3 py-2 whitespace-nowrap">
                    @if($app->proposal_answer === 'yes')
                        <span class="text-green-600 font-bold">はい</span>
                        @if($app->proposal_answered_at)
                            <div class="text-gray-700 text-xs">{{ $app->proposal_answered_at->format('m/d H:i') }}</div>
                        @endif
                    @elseif($app->proposal_answer === 'no')
                        <span class="text-red-500">いいえ</span>
                    @elseif($app->status === 'line_contacted')
                        <span class="text-yellow-500 text-xs">未回答</span>
                    @else
                        <span class="text-gray-800 text-xs">-</span>
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
                        <span class="text-gray-800 text-xs">-</span>
                    @endif
                </td>
                {{-- 他案件状況 --}}
                <td class="px-3 py-2">
                    @if($others->isEmpty())
                        <span class="text-gray-800">-</span>
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
                                $otherTime = $other->invited_at
                                    ? $other->invited_at->format('m/d H:i')
                                      . ($other->invited_end_at ? '〜'.$other->invited_end_at->format('H:i') : '')
                                    : null;
                            @endphp
                            <div class="text-orange-600 whitespace-nowrap text-xs">
                                {{ $other->campaign?->title ?? '不明' }}で{{ $otherLabel }}
                                @if($otherTime) / {{ $otherTime }} @endif
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
                        <span class="text-gray-800 text-xs">制限なし</span>
                    @endif
                </td>
                {{-- 操作 --}}
                <td class="px-3 py-2 whitespace-nowrap">
                    <div class="flex gap-1 flex-wrap">
                        @if($app->status === 'pending' && !$isLocked)
                        <button type="button"
                                onclick="openProposalModal({{ $app->id }}, '{{ addslashes($user?->name ?? '') }}', '{{ route('admin.applications.status', $app) }}', {{ ($campaign->campaign_type === 'pr' && $campaign->pr_media === 'IF') ? 'true' : 'false' }})"
                                class="bg-purple-500 text-white px-1.5 py-0.5 rounded hover:bg-purple-600 text-xs">
                            打診
                        </button>
                        @endif
                        @if($app->status === 'line_contacted')
                        <form method="POST" action="{{ route('admin.applications.status', $app) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="scheduled">
                            <button type="submit" class="bg-indigo-500 text-white px-1.5 py-0.5 rounded hover:bg-indigo-600 text-xs">予約</button>
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
                            <button type="submit" class="bg-teal-500 text-white px-1.5 py-0.5 rounded hover:bg-teal-600 text-xs">完了</button>
                        </form>
                        @endif
                        @if(!in_array($app->status, ['cancelled','completed','reported','approved','point_granted']))
                        <form method="POST" action="{{ route('admin.applications.status', $app) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <button type="submit" class="bg-red-500 text-white px-1.5 py-0.5 rounded hover:bg-red-600 text-xs"
                                    onclick="return confirm('キャンセルしますか？')">取消</button>
                        </form>
                        @endif
                        @if($app->continuation_wish === '希望' && in_array($app->status, ['completed','reported','approved']))
                        <button type="button"
                                class="bg-green-500 text-white px-1.5 py-0.5 rounded hover:bg-green-600 text-xs"
                                title="{{ $app->continuation_response ? '回答済: '.($app->continuation_response === 'possible' ? '可能' : '不可') : '未回答' }}"
                                onclick="openContModal('{{ route('admin.applications.continuation_line', $app) }}', '{{ addslashes($app->user?->name) }}')">
                            継続LINE{{ $app->continuation_response ? '✓' : '' }}
                        </button>
                        @endif
                        <a href="{{ route('admin.applications.show', $app) }}"
                           class="bg-gray-500 text-white px-1.5 py-0.5 rounded hover:bg-gray-600 text-xs">詳細</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="18" class="px-4 py-8 text-center text-gray-700 dark:text-gray-500">応募がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $applications->links() }}</div>

{{-- 打診モーダル --}}
<div id="proposalModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl p-6 w-96">
        <h3 class="font-bold text-gray-800 mb-1">打診送信</h3>
        <p id="proposalUserName" class="text-sm text-gray-700 mb-3"></p>

        {{-- PR+IF案件のみ表示するタブ --}}
        <div id="proposalTabs" class="hidden flex border-b border-gray-200 mb-4">
            <button type="button" id="modalTabNormal" onclick="switchModalTab('normal')"
                    class="flex-1 py-2 text-sm font-medium border-b-2 border-pink-500 text-pink-600">
                通常打診
            </button>
            <button type="button" id="modalTabPr" onclick="switchModalTab('pr')"
                    class="flex-1 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                PR打診
            </button>
        </div>

        <form id="proposalForm" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="line_contacted">
            <input type="hidden" name="invited_at" id="hiddenInvitedAt">
            <input type="hidden" name="invited_end_at" id="hiddenInvitedEndAt">

            {{-- 通常打診フォーム --}}
            <div id="normalProposalForm">
                <div>
                    <label class="block text-xs text-gray-700 mb-1">案内予定日 <span class="text-red-400">*</span></label>
                    <input type="date" id="proposalDate"
                           class="w-full border rounded px-3 py-2 text-sm"
                           value="{{ now()->addDay()->format('Y-m-d') }}"
                           onchange="buildDatetimes()">
                </div>
                <div class="mt-3">
                    <label class="block text-xs text-gray-700 mb-2">時間帯 <span class="text-red-400">*</span></label>
                    <div class="grid grid-cols-2 gap-2" id="slotButtons">
                        @php
                        $modalSlots = [
                            ['label'=>'10:00〜13:00','start'=>'10:00','end'=>'13:00'],
                            ['label'=>'14:00〜17:00','start'=>'14:00','end'=>'17:00'],
                            ['label'=>'18:00〜20:00','start'=>'18:00','end'=>'20:00'],
                            ['label'=>'21:00〜24:00','start'=>'21:00','end'=>'23:59'],
                        ];
                        @endphp
                        @foreach($modalSlots as $slot)
                        <button type="button"
                                class="slot-btn border rounded px-3 py-2 text-sm text-gray-700 hover:bg-pink-50 hover:border-pink-400"
                                data-start="{{ $slot['start'] }}"
                                data-end="{{ $slot['end'] }}"
                                onclick="selectModalSlot(this)">
                            {{ $slot['label'] }}
                        </button>
                        @endforeach
                    </div>
                    <input type="hidden" id="selectedSlotStart">
                </div>
                <p id="slotError" class="hidden text-xs text-red-500 mt-1">時間帯を選択してください</p>
            </div>

            {{-- PR打診フォーム（PR+IFのみ表示） --}}
            <div id="prProposalForm" class="hidden">
                <div class="bg-pink-50 border border-pink-200 rounded-lg p-3 text-xs text-pink-700 mb-3">
                    期限内に実施可能な場合すぐに案内を送るパターンです。<br>
                    invited_at（開始時間）は未設定のまま送信し、ユーザーが確認した時点でセットされます。
                </div>
                <div>
                    <label class="block text-xs text-gray-700 mb-1">実施期限（締め切り日時） <span class="text-red-400">*</span></label>
                    <input type="date" id="prDeadlineDate"
                           class="w-full border rounded px-3 py-2 text-sm mb-2"
                           value="{{ now()->format('Y-m-d') }}"
                           onchange="buildPrDeadline()">
                    <select id="prDeadlineHour"
                            class="w-full border rounded px-3 py-2 text-sm"
                            onchange="buildPrDeadline()">
                        @for($h = 0; $h < 24; $h++)
                        <option value="{{ $h }}" @selected($h === now()->addHours(3)->hour)>
                            〜{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00
                        </option>
                        @endfor
                    </select>
                </div>
                <p id="prDeadlineError" class="hidden text-xs text-red-500 mt-1">締め切り日時を入力してください</p>
            </div>

            <div>
                <label class="block text-xs text-gray-700 mb-1">メモ</label>
                <input type="text" name="memo"
                       class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div class="flex gap-2 pt-1">
                <button type="button" onclick="submitProposal()"
                        class="flex-1 bg-pink-500 text-white py-2 rounded text-sm hover:bg-pink-600 font-medium">
                    打診送信
                </button>
                <button type="button" onclick="closeProposalModal()"
                        class="flex-1 bg-gray-500 text-white py-2 rounded text-sm hover:bg-gray-600">
                    キャンセル
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let _selectedSlotStart = null, _selectedSlotEnd = null, _modalTab = 'normal', _modalIsPrIf = false;

function selectModalSlot(btn) {
    document.querySelectorAll('.slot-btn').forEach(b => {
        b.classList.remove('bg-pink-500','text-white','border-pink-500');
        b.classList.add('text-gray-700');
    });
    btn.classList.add('bg-pink-500','text-white','border-pink-500');
    btn.classList.remove('text-gray-700');
    _selectedSlotStart = btn.dataset.start;
    _selectedSlotEnd   = btn.dataset.end;
    document.getElementById('selectedSlotStart').value = _selectedSlotStart;
    buildDatetimes();
}

function buildDatetimes() {
    const date = document.getElementById('proposalDate').value;
    if (!date || !_selectedSlotStart) return;
    document.getElementById('hiddenInvitedAt').value    = date + ' ' + _selectedSlotStart + ':00';
    document.getElementById('hiddenInvitedEndAt').value = date + ' ' + _selectedSlotEnd   + ':00';
}

function buildPrDeadline() {
    const date = document.getElementById('prDeadlineDate').value;
    const hour = document.getElementById('prDeadlineHour').value;
    document.getElementById('hiddenInvitedAt').value    = '';
    document.getElementById('hiddenInvitedEndAt').value = (date && hour !== '')
        ? date + ' ' + String(hour).padStart(2, '0') + ':00:00'
        : '';
}

function switchModalTab(tab) {
    _modalTab = tab;
    var isNormal = tab === 'normal';
    document.getElementById('normalProposalForm').classList.toggle('hidden', !isNormal);
    document.getElementById('prProposalForm').classList.toggle('hidden', isNormal);
    document.getElementById('modalTabNormal').className = isNormal
        ? 'flex-1 py-2 text-sm font-medium border-b-2 border-pink-500 text-pink-600'
        : 'flex-1 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700';
    document.getElementById('modalTabPr').className = isNormal
        ? 'flex-1 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700'
        : 'flex-1 py-2 text-sm font-medium border-b-2 border-pink-500 text-pink-600';
    if (isNormal) {
        document.getElementById('hiddenInvitedAt').value    = '';
        document.getElementById('hiddenInvitedEndAt').value = '';
        buildDatetimes();
    } else {
        buildPrDeadline();
    }
}

function openProposalModal(appId, userName, actionUrl, isPrIf) {
    _modalIsPrIf = !!isPrIf;
    document.getElementById('proposalUserName').textContent = '対象: ' + userName;
    document.getElementById('proposalForm').action = actionUrl;

    // スロット選択リセット
    _selectedSlotStart = null; _selectedSlotEnd = null;
    document.getElementById('selectedSlotStart').value = '';
    document.querySelectorAll('.slot-btn').forEach(b => {
        b.classList.remove('bg-pink-500','text-white','border-pink-500');
        b.classList.add('text-gray-700');
    });
    document.getElementById('hiddenInvitedAt').value    = '';
    document.getElementById('hiddenInvitedEndAt').value = '';

    // PR+IFの場合はタブ表示
    var tabs = document.getElementById('proposalTabs');
    if (_modalIsPrIf) {
        tabs.classList.remove('hidden');
        tabs.classList.add('flex');
    } else {
        tabs.classList.add('hidden');
        tabs.classList.remove('flex');
    }
    // タブを通常打診にリセット
    switchModalTab('normal');

    document.getElementById('proposalModal').classList.remove('hidden');
}

function closeProposalModal() {
    document.getElementById('proposalModal').classList.add('hidden');
}

function submitProposal() {
    if (_modalTab === 'normal') {
        if (!_selectedSlotStart) {
            document.getElementById('slotError').classList.remove('hidden');
            return;
        }
        document.getElementById('slotError').classList.add('hidden');
    } else {
        // PR打診：締め切り日時チェック
        const deadline = document.getElementById('hiddenInvitedEndAt').value;
        if (!deadline) {
            document.getElementById('prDeadlineError').classList.remove('hidden');
            return;
        }
        document.getElementById('prDeadlineError').classList.add('hidden');
    }
    document.getElementById('proposalForm').submit();
}

function copyUrl(url) {
    navigator.clipboard.writeText(url).then(() => alert('URLをコピーしました'));
}

function openContModal(actionUrl, userName) {
    document.getElementById('cont-modal-name').textContent = userName;
    document.getElementById('cont-modal-form').action = actionUrl;
    document.getElementById('cont-modal').classList.remove('hidden');
}
</script>

{{-- 継続依頼LINE送信モーダル --}}
<div id="cont-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm mx-4">
        <h3 class="font-bold text-gray-800 mb-2">継続依頼LINE送信</h3>
        <p class="text-sm text-gray-600 mb-4">
            <span id="cont-modal-name" class="font-medium"></span> さんに継続購入のご案内LINEを送信します。
        </p>
        <div class="flex gap-3 justify-end">
            <button type="button"
                    onclick="document.getElementById('cont-modal').classList.add('hidden')"
                    class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded hover:bg-gray-200">
                キャンセル
            </button>
            <form id="cont-modal-form" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm text-white bg-green-500 rounded hover:bg-green-600">
                    送信する
                </button>
            </form>
        </div>
    </div>
</div>
@endsection


