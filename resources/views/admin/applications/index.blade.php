@extends('layouts.admin')

@section('title', '応募管理')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-4">応募管理</h1>


@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4 text-sm">{{ session('error') }}</div>
@endif

{{-- 案件ステータスタブ --}}
@php
$tabs = [
    'published' => ['label' => '公開中',   'color' => 'bg-green-500'],
    'paused'    => ['label' => '一時停止', 'color' => 'bg-orange-500'],
    'closed'    => ['label' => '終了',     'color' => 'bg-gray-500'],
    'draft'     => ['label' => '下書き',   'color' => 'bg-yellow-500'],
];
@endphp
<div class="flex border-b border-gray-200 mb-0">
    @foreach($tabs as $key => $t)
    <a href="{{ route('admin.applications.index', array_merge(request()->except(['status', 'page']), ['status' => $key])) }}"
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

@include('admin.applications._campaign_tabs', [
    'allCampaigns'     => $campaigns,
    'activeCampaignId' => null,
    'currentStatus'    => $campaignStatus,
])

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

{{-- サブフィルター --}}
<form method="GET" class="bg-white rounded-lg shadow p-3 mb-4 flex flex-wrap gap-3 items-end">
    <input type="hidden" name="status" value="{{ $campaignStatus }}">
    <div>
        <label class="block text-xs text-gray-700 mb-1">案件</label>
        <select name="campaign_id" class="border rounded px-2 py-1 text-sm">
            <option value="">すべて</option>
            @foreach($campaigns as $c)
                <option value="{{ $c->id }}" @selected(request('campaign_id') == $c->id)>{{ $c->title }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-700 mb-1">検索</label>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="ユーザーID/LINE名/氏名/フリガナ"
               class="border rounded px-2 py-1 text-sm w-52">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.applications.index', ['status' => $campaignStatus]) }}"
       class="text-sm text-gray-500 hover:text-gray-700 py-1.5">リセット</a>
</form>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="bg-gray-50 text-gray-800">
            <tr>
                <th class="px-3 py-2 text-left whitespace-nowrap">応募日時</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">LINE表示名</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">名前</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">フリガナ</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">年齢</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">性別</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">継続希望</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">実施可能時間</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">案件名</th>
                <th class="px-3 py-2 text-center whitespace-nowrap">CP</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">ステータス</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">案内日時</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">打診回答</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">LINE送信</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">他案件状況</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">次回案内可能</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($applications as $app)
            @php
                $user        = $app->user;
                $age         = $user?->birthdate ? \Carbon\Carbon::parse($user->birthdate)->age : '-';
                $genderLabel = match($user?->gender) { 'male'=>'男', 'female'=>'女', default=>'-' };
                $others      = $app->other_applications ?? collect();
                $unlockAt    = $app->unlock_at;
                $isLocked    = $app->is_locked;
                $lineJobs    = $app->lineMessageJobs ?? collect();
                $proposalJob = $lineJobs->where('send_type','proposal')->sortByDesc('created_at')->first();
                $guideJob    = $lineJobs->where('send_type','monitor_guide')->where('status','pending')->first()
                            ?? $lineJobs->where('send_type','monitor_guide')->sortByDesc('created_at')->first();
            @endphp
            @php $unlinked = str_starts_with($user?->line_user_id ?? '', 'IMPORT_'); @endphp
            <tr class="{{ $unlinked ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50' }} {{ $isLocked ? 'opacity-70' : '' }}">
                <td class="px-3 py-2 whitespace-nowrap text-gray-700">{{ $app->applied_at->format('m/d H:i') }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $user?->line_display_name ?? '-' }}</td>
                <td class="px-3 py-2 font-medium whitespace-nowrap">{{ $user?->name ?? '（未登録）' }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $user?->name_kana ?? '-' }}</td>
                <td class="px-3 py-2 text-center">{{ $age }}</td>
                <td class="px-3 py-2 text-center">{{ $genderLabel }}</td>
                <td class="px-3 py-2 text-center whitespace-nowrap">
                    @if($app->continuation_wish === '希望')
                        @if($app->continuation_response === 'possible')
                            <span class="text-xs bg-teal-500 text-white px-1.5 py-0.5 rounded-full">OK</span>
                        @elseif($app->continuation_response === 'not_possible')
                            <span class="text-xs bg-red-500 text-white px-1.5 py-0.5 rounded-full">NG</span>
                        @elseif($app->continuation_invite_date)
                            <span class="text-xs bg-yellow-400 text-white px-1.5 py-0.5 rounded-full">確認中</span>
                        @else
                            <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">希望</span>
                        @endif
                    @elseif($app->continuation_wish === '不可')
                        <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">不可</span>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </td>
                <td class="px-3 py-2 text-gray-700">
                    @if($app->purchase_available_times)
                        {{ implode('・', $app->purchase_available_times) }}
                    @elseif($user?->available_times)
                        {{ implode('・', $user->available_times) }}
                    @else
                        -
                    @endif
                </td>
                <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $app->campaign?->title ?? '-' }}</td>
                <td class="px-3 py-2 text-center whitespace-nowrap">
                    @if($app->bonus_amount)
                        <span class="bg-red-100 text-red-600 text-xs px-1.5 py-0.5 rounded font-bold">+{{ number_format($app->bonus_amount) }}円</span>
                    @else
                        <span class="text-gray-300">-</span>
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
                        <span class="text-gray-500">〜{{ $app->invited_end_at->format('H:i') }}</span>
                    @endif
                </td>
                {{-- 打診回答 --}}
                <td class="px-3 py-2 whitespace-nowrap">
                    @if($app->proposal_answer === 'yes')
                        <span class="text-green-600 font-bold">はい</span>
                        @if($app->proposal_answered_at)
                            <div class="text-gray-500 text-xs">{{ $app->proposal_answered_at->format('m/d H:i') }}</div>
                        @endif
                    @elseif($app->proposal_answer === 'no')
                        <span class="text-red-500">いいえ</span>
                    @elseif($app->status === 'line_contacted')
                        <span class="text-yellow-500 text-xs">未回答</span>
                    @else
                        <span class="text-gray-400 text-xs">-</span>
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
                        <span class="text-gray-400 text-xs">-</span>
                    @endif
                </td>
                {{-- 他案件状況 --}}
                <td class="px-3 py-2">
                    @if($others->isEmpty())
                        <span class="text-gray-400">-</span>
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
                        <span class="text-red-500 text-xs">{{ $unlockAt->format('m/d H:i') }}〜打診可能</span>
                    @else
                        <span class="text-gray-400 text-xs">制限なし</span>
                    @endif
                </td>
                {{-- 操作 --}}
                <td class="px-3 py-2 whitespace-nowrap">
                    <div class="flex gap-1 flex-wrap">
                        @if($app->status === 'pending' && !$isLocked)
                        <button type="button"
                                onclick="openProposalModal({{ $app->id }}, '{{ addslashes($user?->name ?? '') }}', '{{ route('admin.applications.status', $app) }}', {{ ($app->campaign?->campaign_type === 'pr' && $app->campaign?->pr_media === 'IF') ? 'true' : 'false' }})"
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
                        <a href="{{ route('admin.applications.show', $app) }}"
                           class="bg-gray-500 text-white px-1.5 py-0.5 rounded hover:bg-gray-600 text-xs">詳細</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="17" class="px-4 py-8 text-center text-gray-700">応募がありません</td>
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
                        $slots = [
                            ['label'=>'10:00〜13:00','start'=>'10:00','end'=>'13:00'],
                            ['label'=>'14:00〜17:00','start'=>'14:00','end'=>'17:00'],
                            ['label'=>'18:00〜20:00','start'=>'18:00','end'=>'20:00'],
                            ['label'=>'21:00〜24:00','start'=>'21:00','end'=>'23:59'],
                        ];
                        @endphp
                        @foreach($slots as $slot)
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
                    ユーザーが確認した時点で開始時間がセットされます。
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
                <input type="text" name="memo" class="w-full border rounded px-3 py-2 text-sm">
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

    _selectedSlotStart = null; _selectedSlotEnd = null;
    document.getElementById('selectedSlotStart').value = '';
    document.querySelectorAll('.slot-btn').forEach(b => {
        b.classList.remove('bg-pink-500','text-white','border-pink-500');
        b.classList.add('text-gray-700');
    });
    document.getElementById('hiddenInvitedAt').value    = '';
    document.getElementById('hiddenInvitedEndAt').value = '';

    var tabs = document.getElementById('proposalTabs');
    if (_modalIsPrIf) {
        tabs.classList.remove('hidden');
        tabs.classList.add('flex');
    } else {
        tabs.classList.add('hidden');
        tabs.classList.remove('flex');
    }
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
        const deadline = document.getElementById('hiddenInvitedEndAt').value;
        if (!deadline) {
            document.getElementById('prDeadlineError').classList.remove('hidden');
            return;
        }
        document.getElementById('prDeadlineError').classList.add('hidden');
    }
    document.getElementById('proposalForm').submit();
}
</script>

@endsection
