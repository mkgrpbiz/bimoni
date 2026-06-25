@extends('layouts.admin')

@section('title', '応募詳細')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.campaigns.applications', $application->campaign) }}"
       class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">← 応募者一覧</a>
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
                <dt class="text-gray-700 dark:text-gray-400">モニター</dt>
                <dd class="font-medium dark:text-gray-200">{{ $application->user->name ?? '（未登録）' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400">案件</dt>
                <dd class="dark:text-gray-200">{{ $application->campaign->title }}</dd>
                <dt class="text-gray-700 dark:text-gray-400">ステータス</dt>
                <dd><span class="px-2 py-0.5 rounded text-xs {{ $application->getStatusColor() }}">{{ $application->getStatusLabel() }}</span></dd>
                <dt class="text-gray-700 dark:text-gray-400">応募日</dt>
                <dd class="dark:text-gray-200">{{ $application->applied_at->format('Y/m/d H:i') }}</dd>
                <dt class="text-gray-700 dark:text-gray-400">当選日</dt>
                <dd class="dark:text-gray-200">{{ $application->selected_at?->format('Y/m/d') ?? '-' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400">LINE案内</dt>
                <dd class="dark:text-gray-200">{{ $application->line_contacted_at?->format('Y/m/d') ?? '-' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400">日程確定</dt>
                <dd class="dark:text-gray-200">{{ $application->schedule_confirmed_at?->format('Y/m/d') ?? '-' }}</dd>
            </dl>
        </div>

        {{-- ステータス変更 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">ステータス変更</h2>
            <form method="POST" action="{{ route('admin.applications.status', $application) }}" class="flex flex-wrap gap-2">
                @csrf @method('PATCH')
                @foreach([
                    'line_contacted' => ['打診中にする',     'bg-pink-500 hover:bg-pink-600'],
                    'scheduled'      => ['予約中にする',     'bg-pink-500 hover:bg-pink-600'],
                    'confirming'     => ['実施確認中にする', 'bg-pink-500 hover:bg-pink-600'],
                    'completed'      => ['実施完了にする',   'bg-pink-500 hover:bg-pink-600'],
                    'cancelled'      => ['キャンセルにする', 'bg-red-500 hover:bg-red-600'],
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

        {{-- 管理メモ --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">管理メモ</h2>
            <form method="POST" action="{{ route('admin.applications.notes', $application) }}">
                @csrf @method('PATCH')
                <textarea name="notes" rows="3"
                          class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm mb-2">{{ $application->notes }}</textarea>
                <button type="submit" class="text-sm bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600">保存</button>
            </form>
        </div>
    </div>

    {{-- モニター情報サイドバー --}}
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">モニター情報</h2>
            @php $user = $application->user; @endphp
            <dl class="text-sm space-y-1">
                <dt class="text-gray-700 dark:text-gray-400">氏名</dt>
                <dd class="font-medium dark:text-gray-200">{{ $user->name ?? '-' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400 mt-2">フリガナ</dt>
                <dd class="dark:text-gray-200">{{ $user->name_kana ?? '-' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400 mt-2">性別</dt>
                <dd class="dark:text-gray-200">{{ match($user->gender ?? '') { 'male' => '男性', 'female' => '女性', 'other' => 'その他', default => '-' } }}</dd>
                <dt class="text-gray-700 dark:text-gray-400 mt-2">生年月日</dt>
                <dd class="dark:text-gray-200">{{ $user->birthdate?->format('Y/m/d') ?? '-' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400 mt-2">エリア</dt>
                <dd class="dark:text-gray-200">{{ $user->area ?? '-' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400 mt-2">実施可能時間帯</dt>
                <dd class="dark:text-gray-200">{{ $user->available_times ? implode('、', $user->available_times) : '-' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400 mt-2">継続希望</dt>
                <dd class="dark:text-gray-200">{{ $application->continuation_wish ?? '-' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400 mt-2">購入可能時間</dt>
                <dd class="dark:text-gray-200 text-xs">{{ $application->purchase_available_times ? implode('・', $application->purchase_available_times) : '-' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400 mt-2">保有ポイント</dt>
                <dd class="font-medium text-pink-600 dark:text-pink-400">{{ number_format($user->point_balance) }} pt</dd>
            </dl>
        </div>
    </div>
</div>
@endsection

