@extends('layouts.admin')

@section('title', '応募管理')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-4">応募管理</h1>

@include('admin.applications._campaign_tabs', ['allCampaigns' => $campaigns, 'activeCampaignId' => null])

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4 text-sm">{{ session('error') }}</div>
@endif

<form method="GET" class="bg-white rounded-lg shadow p-3 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-700 mb-1">名前検索</label>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="名前・フリガナ"
               class="border rounded px-2 py-1 text-sm w-40">
    </div>
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
        <label class="block text-xs text-gray-700 mb-1">ステータス</label>
        <select name="status" class="border rounded px-2 py-1 text-sm">
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
    <button type="submit" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.applications.index') }}" class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">リセット</a>
</form>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="bg-gray-50 text-gray-800">
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
                <th class="px-3 py-2 text-left whitespace-nowrap">ステータス</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">案内日時</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">打診回答</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">LINE送信</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">他案件状況</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">48h制限</th>
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
            <tr class="hover:bg-gray-50 {{ $isLocked ? 'opacity-70' : '' }}">
                <td class="px-3 py-2 whitespace-nowrap text-gray-700">{{ $app->applied_at->format('m/d H:i') }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $user?->erme_respondent_id ?? '-' }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $user?->line_display_name ?? '-' }}</td>
                <td class="px-3 py-2 font-medium whitespace-nowrap">{{ $user?->name ?? '（未登録）' }}</td>
                <td class="px-3 py-2 text-gray-700">{{ $user?->name_kana ?? '-' }}</td>
                <td class="px-3 py-2 text-center">{{ $age }}</td>
                <td class="px-3 py-2 text-center">{{ $genderLabel }}</td>
                <td class="px-3 py-2 text-center">
                    @if($user?->wants_continuation)
                        <span class="text-green-600">○</span>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </td>
                <td class="px-3 py-2 text-gray-700">
                    @if($user?->available_times)
                        {{ implode('・', $user->available_times) }}
                    @else
                        -
                    @endif
                </td>
                <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $app->campaign?->title ?? '-' }}</td>
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
                                onclick="openProposalModal({{ $app->id }}, '{{ addslashes($user?->name ?? '') }}', '{{ route('admin.applications.status', $app) }}')"
                                class="bg-pink-500 text-white px-1.5 py-0.5 rounded hover:bg-pink-600 text-xs">
                            打診
                        </button>
                        @endif
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
                            <button type="submit" class="bg-pink-500 text-white px-1.5 py-0.5 rounded hover:bg-pink-600 text-xs">完了</button>
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
        <p id="proposalUserName" class="text-sm text-gray-700 mb-4"></p>
        <form id="proposalForm" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="line_contacted">
            <input type="hidden" name="invited_at" id="hiddenInvitedAt">
            <input type="hidden" name="invited_end_at" id="hiddenInvitedEndAt">
            <div>
                <label class="block text-xs text-gray-700 mb-1">案内予定日 <span class="text-red-400">*</span></label>
                <input type="date" id="proposalDate" required
                       class="w-full border rounded px-3 py-2 text-sm"
                       value="{{ now()->addDay()->format('Y-m-d') }}"
                       onchange="buildDatetimes()">
            </div>
            <div>
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
                            onclick="selectSlot(this)">
                        {{ $slot['label'] }}
                    </button>
                    @endforeach
                </div>
                <input type="hidden" id="selectedSlotStart" required>
            </div>
            <div>
                <label class="block text-xs text-gray-700 mb-1">メモ</label>
                <input type="text" name="memo" class="w-full border rounded px-3 py-2 text-sm">
            </div>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 bg-pink-500 text-white py-2 rounded text-sm hover:bg-pink-600 font-medium">打診送信</button>
                <button type="button" onclick="closeProposalModal()"
                        class="flex-1 bg-gray-500 text-white py-2 rounded text-sm hover:bg-gray-600">キャンセル</button>
            </div>
        </form>
    </div>
</div>

<script>
let _selectedSlotStart = null, _selectedSlotEnd = null;
function selectSlot(btn) {
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
function openProposalModal(appId, userName, actionUrl) {
    document.getElementById('proposalUserName').textContent = '対象: ' + userName;
    document.getElementById('proposalForm').action = actionUrl;
    _selectedSlotStart = null; _selectedSlotEnd = null;
    document.getElementById('selectedSlotStart').value = '';
    document.querySelectorAll('.slot-btn').forEach(b => {
        b.classList.remove('bg-pink-500','text-white','border-pink-500');
        b.classList.add('text-gray-700');
    });
    document.getElementById('proposalModal').classList.remove('hidden');
}
function closeProposalModal() {
    document.getElementById('proposalModal').classList.add('hidden');
}
</script>

@endsection
