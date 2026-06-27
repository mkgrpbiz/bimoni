@extends('layouts.admin')

@section('title', '打診予約管理')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">打診予約管理</h1>

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-4 py-2 rounded mb-4 text-sm">{{ session('error') }}</div>
@endif

{{-- アラート --}}
@if($duplicateAlerts->isNotEmpty() || $overCapacityAlerts->isNotEmpty() || $tomorrowUnderAlerts->isNotEmpty())
<div class="space-y-2 mb-4">
    @foreach($duplicateAlerts as $dup)
    @php
        $dupCampaign = \App\Models\Campaign::find($dup->campaign_id);
        $dupTime = \Carbon\Carbon::parse($dup->invited_at)->format('m/d H:i');
    @endphp
    <div class="bg-red-50 border border-red-300 rounded-lg px-4 py-2 text-sm text-red-700 flex items-center gap-2">
        <span class="font-bold">⚠ ダブルブッキング</span>
        <span>{{ $dupCampaign?->title ?? '不明' }} / {{ $dupTime }} に {{ $dup->cnt }}件入っています</span>
    </div>
    @endforeach

    @foreach($overCapacityAlerts as $over)
    <div class="bg-orange-50 border border-orange-300 rounded-lg px-4 py-2 text-sm text-orange-700 flex items-center gap-2">
        <span class="font-bold">⚠ 目標件数オーバー</span>
        <span>{{ $over['slot']->campaign?->title ?? '不明' }} / {{ $over['slot']->target_date->format('m/d') }} — 目標 {{ $over['planned'] }}件に対し {{ $over['booked'] }}件</span>
    </div>
    @endforeach

    @if($tomorrowUnderAlerts->isNotEmpty())
    <div class="bg-yellow-50 border border-yellow-300 rounded-lg px-4 py-2 text-sm text-yellow-800">
        <div class="font-bold mb-1">翌日未達成打診 <span class="font-normal text-xs ml-1">{{ now()->addDay()->format('m/d') }}</span></div>
        <div class="flex flex-wrap gap-2">
            @foreach($tomorrowUnderAlerts as $under)
            <span class="bg-yellow-100 border border-yellow-300 rounded px-2 py-0.5 text-xs font-medium">
                {{ $under['slot']->campaign?->title ?? '不明' }}（打診{{ $under['booked'] }}/目標{{ $under['planned'] }}）
            </span>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif

{{-- フィルター --}}
<form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-4 flex gap-3 items-end flex-wrap">
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">案件</label>
        <select name="campaign_id" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
            <option value="">すべて</option>
            @foreach($campaigns as $c)
                <option value="{{ $c->id }}" @selected(request('campaign_id') == $c->id)>{{ $c->title }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">名前検索</label>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="名前・フリガナ"
               class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm w-40">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.proposal_reservations.index') }}" class="bg-gray-500 text-white px-3 py-1.5 rounded text-sm hover:bg-gray-600">リセット</a>
</form>

{{-- テーブル --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-3 py-2 text-left whitespace-nowrap">応募日時</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">ユーザーID</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">LINE表示名</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">名前</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">フリガナ</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">年齢</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">性別</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">継続可否</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">実施可能時間</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">案件名</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">CP</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">ステータス</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">案内日時</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">打診回答</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">LINE送信</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">他案件状況</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($applications as $app)
            @php
                $user        = $app->user;
                $age         = $user?->birthdate ? \Carbon\Carbon::parse($user->birthdate)->age : '-';
                $genderLabel = match($user?->gender) { 'male'=>'男', 'female'=>'女', default=>'-' };
                $others      = $app->other_applications ?? collect();
                $unlockAt    = $app->unlock_at;
                $lineJobs    = $app->lineMessageJobs ?? collect();
                $proposalJob = $lineJobs->where('send_type','proposal')->sortByDesc('created_at')->first();
                $guideJob    = $lineJobs->where('send_type','monitor_guide')->where('status','pending')->first()
                            ?? $lineJobs->where('send_type','monitor_guide')->sortByDesc('created_at')->first();
                $isPrIf      = $app->campaign->campaign_type === 'pr' && $app->campaign->pr_media === 'IF';
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-400">{{ $app->applied_at?->format('m/d H:i') ?? '-' }}</td>
                <td class="px-3 py-2 text-gray-700 dark:text-gray-400">{{ $user?->erme_respondent_id ?? '-' }}</td>
                <td class="px-3 py-2 text-gray-700 dark:text-gray-400">{{ $user?->line_display_name ?? '-' }}</td>
                <td class="px-3 py-2 font-medium whitespace-nowrap dark:text-gray-200">{{ $user?->name ?? '（未登録）' }}</td>
                <td class="px-3 py-2 text-gray-700 dark:text-gray-400">{{ $user?->name_kana ?? '-' }}</td>
                <td class="px-3 py-2 text-center dark:text-gray-300">{{ $age }}</td>
                <td class="px-3 py-2 text-center dark:text-gray-300">{{ $genderLabel }}</td>
                <td class="px-3 py-2 text-center whitespace-nowrap">
                    @if($app->continuation_wish === '希望')
                        <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">希望</span>
                    @elseif($app->continuation_wish === '不可')
                        <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">不可</span>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </td>
                <td class="px-3 py-2 text-gray-700 dark:text-gray-400 text-xs">
                    @if($app->purchase_available_times)
                        {{ implode('・', $app->purchase_available_times) }}
                    @elseif($user?->available_times)
                        {{ implode('・', $user->available_times) }}
                    @else
                        -
                    @endif
                </td>
                <td class="px-3 py-2 whitespace-nowrap font-medium dark:text-gray-200">{{ $app->campaign->title }}</td>
                <td class="px-3 py-2 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $app->campaign->getTypeLabel() }}</td>
                <td class="px-3 py-2 whitespace-nowrap">
                    <span class="px-1.5 py-0.5 rounded text-xs {{ $app->getStatusColor() }}">{{ $app->getStatusLabel() }}</span>
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-400">
                    {{ $app->invited_at?->format('m/d H:i') ?? '-' }}
                    @if($app->invited_end_at)
                        〜{{ $app->invited_end_at->format('H:i') }}
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
                    @if(!$proposalJob && !$guideJob)<span class="text-gray-400">-</span>@endif
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
                {{-- 操作 --}}
                <td class="px-3 py-2 whitespace-nowrap">
                    <div class="flex gap-1 flex-wrap">
                        @if($app->status === 'line_contacted')
                        <form method="POST" action="{{ route('admin.applications.status', $app) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="scheduled">
                            <button type="submit" class="bg-pink-500 text-white px-1.5 py-0.5 rounded hover:bg-pink-600 text-xs">予約</button>
                        </form>
                        @endif
                        @if($app->status === 'scheduled')
                        <form method="POST" action="{{ route('admin.applications.status', $app) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="confirming">
                            <button type="submit" class="bg-pink-500 text-white px-1.5 py-0.5 rounded hover:bg-pink-600 text-xs">実施確認</button>
                        </form>
                        @endif
                        @if($app->status === 'confirming')
                        <form method="POST" action="{{ route('admin.applications.status', $app) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="bg-teal-500 text-white px-1.5 py-0.5 rounded hover:bg-teal-600 text-xs">実施完了</button>
                        </form>
                        @endif
                        @if(in_array($app->status, ['line_contacted', 'scheduled']))
                        <button type="button"
                                onclick="openReProposalModal({{ $app->id }}, '{{ addslashes($user?->name ?? '') }}', '{{ route('admin.applications.re_proposal', $app) }}', {{ $isPrIf ? 'true' : 'false' }})"
                                class="bg-yellow-500 text-white px-1.5 py-0.5 rounded hover:bg-yellow-600 text-xs">
                            再打診
                        </button>
                        @endif
                        <a href="{{ route('admin.applications.show', $app) }}"
                           class="bg-gray-500 text-white px-1.5 py-0.5 rounded hover:bg-gray-600 text-xs">詳細</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="17" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">打診中・予約中・実施確認中の応募がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $applications->links() }}</div>

{{-- 再打診モーダル --}}
<div id="reProposalModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl p-6 w-96">
        <h3 class="font-bold text-gray-800 mb-1">再打診送信</h3>
        <p id="reProposalUserName" class="text-sm text-gray-600 mb-3"></p>

        <div id="reProposalTabs" class="hidden flex border-b border-gray-200 mb-4">
            <button type="button" id="reModalTabNormal" onclick="switchReModalTab('normal')"
                    class="flex-1 py-2 text-sm font-medium border-b-2 border-pink-500 text-pink-600">
                通常打診
            </button>
            <button type="button" id="reModalTabPr" onclick="switchReModalTab('pr')"
                    class="flex-1 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                PR打診
            </button>
        </div>

        <form id="reProposalForm" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="invited_at" id="reHiddenInvitedAt">
            <input type="hidden" name="invited_end_at" id="reHiddenInvitedEndAt">

            <div id="reNormalForm">
                <div>
                    <label class="block text-xs text-gray-700 mb-1">案内予定日 <span class="text-red-400">*</span></label>
                    <input type="date" id="reProposalDate"
                           class="w-full border rounded px-3 py-2 text-sm"
                           value="{{ now()->addDay()->format('Y-m-d') }}"
                           onchange="reBuildDatetimes()">
                </div>
                <div class="mt-3">
                    <label class="block text-xs text-gray-700 mb-2">時間帯 <span class="text-red-400">*</span></label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach([
                            ['label'=>'10:00〜13:00','start'=>'10:00','end'=>'13:00'],
                            ['label'=>'14:00〜17:00','start'=>'14:00','end'=>'17:00'],
                            ['label'=>'18:00〜20:00','start'=>'18:00','end'=>'20:00'],
                            ['label'=>'21:00〜24:00','start'=>'21:00','end'=>'23:59'],
                        ] as $rslot)
                        <button type="button"
                                class="re-slot-btn border rounded px-3 py-2 text-sm text-gray-700 hover:bg-yellow-50 hover:border-yellow-400"
                                data-start="{{ $rslot['start'] }}"
                                data-end="{{ $rslot['end'] }}"
                                onclick="selectReSlot(this)">
                            {{ $rslot['label'] }}
                        </button>
                        @endforeach
                    </div>
                    <input type="hidden" id="reSelectedSlotStart">
                </div>
                <p id="reSlotError" class="hidden text-xs text-red-500 mt-1">時間帯を選択してください</p>
            </div>

            <div id="rePrForm" class="hidden">
                <div>
                    <label class="block text-xs text-gray-700 mb-1">実施期限（締め切り日時） <span class="text-red-400">*</span></label>
                    <input type="date" id="rePrDeadlineDate"
                           class="w-full border rounded px-3 py-2 text-sm mb-2"
                           value="{{ now()->format('Y-m-d') }}"
                           onchange="reBuildPrDeadline()">
                    <select id="rePrDeadlineHour"
                            class="w-full border rounded px-3 py-2 text-sm"
                            onchange="reBuildPrDeadline()">
                        @for($h = 0; $h < 24; $h++)
                        <option value="{{ $h }}" @selected($h === now()->addHours(3)->hour)>
                            〜{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00
                        </option>
                        @endfor
                    </select>
                </div>
                <p id="rePrDeadlineError" class="hidden text-xs text-red-500 mt-1">締め切り日時を入力してください</p>
            </div>

            <div>
                <label class="block text-xs text-gray-700 mb-1">メモ</label>
                <input type="text" name="memo" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div class="flex gap-2 pt-1">
                <button type="button" onclick="submitReProposal()"
                        class="flex-1 bg-yellow-500 text-white py-2 rounded text-sm hover:bg-yellow-600 font-medium">
                    再打診送信
                </button>
                <button type="button" onclick="closeReProposalModal()"
                        class="flex-1 bg-gray-500 text-white py-2 rounded text-sm hover:bg-gray-600">
                    キャンセル
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let _reSlotStart = null, _reSlotEnd = null, _reModalTab = 'normal', _reIsPrIf = false;

function selectReSlot(btn) {
    document.querySelectorAll('.re-slot-btn').forEach(b => {
        b.classList.remove('bg-yellow-500','text-white','border-yellow-500');
        b.classList.add('text-gray-700');
    });
    btn.classList.add('bg-yellow-500','text-white','border-yellow-500');
    btn.classList.remove('text-gray-700');
    _reSlotStart = btn.dataset.start;
    _reSlotEnd   = btn.dataset.end;
    reBuildDatetimes();
}

function reBuildDatetimes() {
    const date = document.getElementById('reProposalDate').value;
    if (!date || !_reSlotStart) return;
    document.getElementById('reHiddenInvitedAt').value    = date + ' ' + _reSlotStart + ':00';
    document.getElementById('reHiddenInvitedEndAt').value = date + ' ' + _reSlotEnd   + ':00';
}

function reBuildPrDeadline() {
    const date = document.getElementById('rePrDeadlineDate').value;
    const hour = document.getElementById('rePrDeadlineHour').value;
    document.getElementById('reHiddenInvitedAt').value    = '';
    document.getElementById('reHiddenInvitedEndAt').value = (date && hour !== '')
        ? date + ' ' + String(hour).padStart(2, '0') + ':00:00'
        : '';
}

function switchReModalTab(tab) {
    _reModalTab = tab;
    var isNormal = tab === 'normal';
    document.getElementById('reNormalForm').classList.toggle('hidden', !isNormal);
    document.getElementById('rePrForm').classList.toggle('hidden', isNormal);
    document.getElementById('reModalTabNormal').className = isNormal
        ? 'flex-1 py-2 text-sm font-medium border-b-2 border-pink-500 text-pink-600'
        : 'flex-1 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700';
    document.getElementById('reModalTabPr').className = isNormal
        ? 'flex-1 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700'
        : 'flex-1 py-2 text-sm font-medium border-b-2 border-pink-500 text-pink-600';
    document.getElementById('reHiddenInvitedAt').value    = '';
    document.getElementById('reHiddenInvitedEndAt').value = '';
    if (isNormal) reBuildDatetimes(); else reBuildPrDeadline();
}

function openReProposalModal(appId, userName, actionUrl, isPrIf) {
    _reIsPrIf = !!isPrIf;
    document.getElementById('reProposalUserName').textContent = '対象: ' + userName;
    document.getElementById('reProposalForm').action = actionUrl;

    _reSlotStart = null; _reSlotEnd = null;
    document.getElementById('reSelectedSlotStart').value = '';
    document.querySelectorAll('.re-slot-btn').forEach(b => {
        b.classList.remove('bg-yellow-500','text-white','border-yellow-500');
        b.classList.add('text-gray-700');
    });
    document.getElementById('reHiddenInvitedAt').value    = '';
    document.getElementById('reHiddenInvitedEndAt').value = '';

    var tabs = document.getElementById('reProposalTabs');
    if (_reIsPrIf) {
        tabs.classList.remove('hidden');
        tabs.classList.add('flex');
    } else {
        tabs.classList.add('hidden');
        tabs.classList.remove('flex');
    }
    switchReModalTab('normal');
    document.getElementById('reProposalModal').classList.remove('hidden');
}

function closeReProposalModal() {
    document.getElementById('reProposalModal').classList.add('hidden');
}

function submitReProposal() {
    if (_reModalTab === 'normal') {
        if (!_reSlotStart) {
            document.getElementById('reSlotError').classList.remove('hidden');
            return;
        }
        document.getElementById('reSlotError').classList.add('hidden');
    } else {
        if (!document.getElementById('reHiddenInvitedEndAt').value) {
            document.getElementById('rePrDeadlineError').classList.remove('hidden');
            return;
        }
        document.getElementById('rePrDeadlineError').classList.add('hidden');
    }
    document.getElementById('reProposalForm').submit();
}
</script>
@endsection
