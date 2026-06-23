@extends('layouts.admin')

@section('title', '応募詳細')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.campaigns.applications', $application->campaign) }}" class="text-gray-400 hover:text-gray-600">← 応募者一覧</a>
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">応募詳細</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- 基本情報 --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">応募情報</h2>
            <dl class="grid grid-cols-2 gap-2 text-sm">
                <dt class="text-gray-500 dark:text-gray-400">モニター</dt>
                <dd class="font-medium dark:text-gray-200">{{ $application->user->name ?? '（未登録）' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">案件</dt>
                <dd class="dark:text-gray-200">{{ $application->campaign->title }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">ステータス</dt>
                <dd><span class="px-2 py-0.5 rounded text-xs {{ $application->getStatusColor() }}">{{ $application->getStatusLabel() }}</span></dd>
                <dt class="text-gray-500 dark:text-gray-400">応募日</dt>
                <dd class="dark:text-gray-200">{{ $application->applied_at->format('Y/m/d H:i') }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">当選日</dt>
                <dd class="dark:text-gray-200">{{ $application->selected_at?->format('Y/m/d') ?? '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">LINE案内</dt>
                <dd class="dark:text-gray-200">{{ $application->line_contacted_at?->format('Y/m/d') ?? '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">日程確定</dt>
                <dd class="dark:text-gray-200">{{ $application->schedule_confirmed_at?->format('Y/m/d') ?? '-' }}</dd>
            </dl>
        </div>

        {{-- ステータス変更 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">ステータス変更</h2>
            <form method="POST" action="{{ route('admin.applications.status', $application) }}" class="flex flex-wrap gap-2">
                @csrf @method('PATCH')
                @foreach([
                    'selected'       => ['当選にする', 'bg-blue-600 hover:bg-blue-700'],
                    'rejected'       => ['落選にする', 'bg-red-500 hover:bg-red-600'],
                    'line_contacted' => ['LINE案内済にする', 'bg-purple-600 hover:bg-purple-700'],
                    'completed'      => ['実施完了にする', 'bg-teal-600 hover:bg-teal-700'],
                    'cancelled'      => ['キャンセルにする', 'bg-gray-500 hover:bg-gray-600'],
                ] as $status => [$label, $color])
                    @if($application->status !== $status)
                    <button type="submit" name="status" value="{{ $status }}"
                            class="text-white text-xs px-3 py-1.5 rounded {{ $color }}">
                        {{ $label }}
                    </button>
                    @endif
                @endforeach
            </form>
        </div>

        {{-- 打診・日程管理（体験モニターのみ） --}}
        @if($application->campaign->campaign_type === 'experience')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">打診・日程管理</h2>

            @if($application->schedules->isNotEmpty())
            <div class="mb-4 space-y-2">
                @foreach($application->schedules as $schedule)
                <div class="border dark:border-gray-600 rounded p-3 text-sm">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-medium dark:text-gray-200">
                            @if($schedule->status === 'confirmed') ✅ 確定
                            @elseif($schedule->status === 'cancelled') ❌ キャンセル
                            @else ⏳ 打診中
                            @endif
                        </span>
                        <span class="text-xs text-gray-400">{{ $schedule->created_at->format('Y/m/d') }}</span>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 text-xs mb-1">候補日：
                        {{ collect($schedule->proposed_dates)->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y/m/d H:i'))->join(' / ') }}
                    </p>
                    @if($schedule->confirmed_datetime)
                        <p class="text-green-600 dark:text-green-400 text-xs font-medium">確定日時：{{ $schedule->confirmed_datetime->format('Y/m/d H:i') }}</p>
                    @endif
                    @if($schedule->status === 'proposing')
                    <form method="POST" action="{{ route('admin.schedules.confirm', $schedule) }}" class="mt-2 flex gap-2 items-center">
                        @csrf @method('PATCH')
                        <input type="datetime-local" name="confirmed_datetime"
                               class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-xs" required>
                        <button type="submit" class="text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700">日程確定</button>
                    </form>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('admin.schedules.store', $application) }}">
                @csrf
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">新しい打診日程を登録（最大3候補）</p>
                @for($i = 0; $i < 3; $i++)
                <div class="mb-2">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-0.5">候補{{ $i+1 }}</label>
                    <input type="datetime-local" name="proposed_dates[]"
                           class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm w-full">
                </div>
                @endfor
                <textarea name="notes" rows="2" placeholder="メモ"
                          class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm mt-1 mb-2"></textarea>
                <button type="submit" class="bg-pink-600 text-white text-sm px-4 py-1.5 rounded hover:bg-pink-700">打診を送る</button>
            </form>
        </div>
        @endif

        {{-- 管理メモ --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">管理メモ</h2>
            <form method="POST" action="{{ route('admin.applications.notes', $application) }}">
                @csrf @method('PATCH')
                <textarea name="notes" rows="3"
                          class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm mb-2">{{ $application->notes }}</textarea>
                <button type="submit" class="text-sm bg-gray-600 text-white px-3 py-1.5 rounded hover:bg-gray-700">保存</button>
            </form>
        </div>
    </div>

    {{-- モニター情報サイドバー --}}
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">モニター情報</h2>
            @php $user = $application->user; @endphp
            <dl class="text-sm space-y-1">
                <dt class="text-gray-500 dark:text-gray-400">氏名</dt>
                <dd class="font-medium dark:text-gray-200">{{ $user->name ?? '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400 mt-2">フリガナ</dt>
                <dd class="dark:text-gray-200">{{ $user->name_kana ?? '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400 mt-2">性別</dt>
                <dd class="dark:text-gray-200">{{ match($user->gender ?? '') { 'male' => '男性', 'female' => '女性', 'other' => 'その他', default => '-' } }}</dd>
                <dt class="text-gray-500 dark:text-gray-400 mt-2">生年月日</dt>
                <dd class="dark:text-gray-200">{{ $user->birthdate?->format('Y/m/d') ?? '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400 mt-2">エリア</dt>
                <dd class="dark:text-gray-200">{{ $user->area ?? '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400 mt-2">実施可能時間帯</dt>
                <dd class="dark:text-gray-200">{{ $user->available_times ? implode('、', $user->available_times) : '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400 mt-2">継続希望</dt>
                <dd class="dark:text-gray-200">{{ $user->wants_continuation ? 'あり' : 'なし' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400 mt-2">保有ポイント</dt>
                <dd class="font-medium text-pink-600 dark:text-pink-400">{{ number_format($user->point_balance) }} pt</dd>
            </dl>
        </div>
    </div>
</div>
@endsection
