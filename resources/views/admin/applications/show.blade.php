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
                <dt class="text-gray-700 dark:text-gray-400">ユーザー名</dt>
                <dd class="font-medium dark:text-gray-200">{{ $application->user->name ?? '（未登録）' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400">案件名</dt>
                <dd class="dark:text-gray-200">{{ $application->campaign->title }}</dd>
                <dt class="text-gray-700 dark:text-gray-400">応募日時</dt>
                <dd class="dark:text-gray-200">{{ $application->applied_at->format('Y/m/d H:i') }}</dd>
                <dt class="text-gray-700 dark:text-gray-400">案内日時</dt>
                <dd class="dark:text-gray-200">{{ $application->invited_at?->format('Y/m/d H:i') ?? '-' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400">ステータス</dt>
                <dd><span class="px-2 py-0.5 rounded text-xs {{ $application->getStatusColor() }}">{{ $application->getStatusLabel() }}</span></dd>
                <dt class="text-gray-700 dark:text-gray-400">継続ステータス</dt>
                <dd>
                    @if($application->continuation_responded_at && $application->continuation_response === 'possible')
                        <span class="text-xs bg-teal-500 text-white px-1.5 py-0.5 rounded-full">OK</span>
                    @elseif($application->continuation_responded_at && $application->continuation_response === 'not_possible')
                        <span class="text-xs bg-red-500 text-white px-1.5 py-0.5 rounded-full">NG</span>
                    @elseif($application->continuation_sent_at)
                        <span class="text-xs bg-yellow-400 text-white px-1.5 py-0.5 rounded-full">確認中</span>
                    @elseif($application->continuation_wish === '希望')
                        <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">希望</span>
                    @elseif($application->continuation_wish === '不可')
                        <span class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full">不可</span>
                    @else
                        <span class="text-xs text-gray-400">-</span>
                    @endif
                </dd>
            </dl>
        </div>

        {{-- ステータス変更 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">ステータス変更</h2>
            <form method="POST" action="{{ route('admin.applications.status', $application) }}" class="flex flex-wrap gap-2">
                @csrf @method('PATCH')
                @foreach([
                    'pending'        => ['応募中にする',     'bg-gray-500 hover:bg-gray-600'],
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

        {{-- 継続情報の編集 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">継続情報の編集</h2>
            <form method="POST" action="{{ route('admin.applications.continuation_update', $application) }}" class="flex flex-wrap items-end gap-3">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">継続希望</label>
                    <select name="continuation_wish" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1.5 text-sm">
                        <option value="" @selected(!$application->continuation_wish)>未設定</option>
                        <option value="希望" @selected($application->continuation_wish === '希望')>希望</option>
                        <option value="不可" @selected($application->continuation_wish === '不可')>不可</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">継続回答</label>
                    <select name="continuation_response" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1.5 text-sm">
                        <option value="" @selected(!$application->continuation_response)>未回答</option>
                        <option value="possible" @selected($application->continuation_response === 'possible')>OK</option>
                        <option value="not_possible" @selected($application->continuation_response === 'not_possible')>NG</option>
                    </select>
                </div>
                <button type="submit" class="text-sm bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600">更新</button>
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
            </dl>
            @if($user)
            <div class="mt-3">
                <a href="{{ route('admin.users.show', $user) }}"
                   class="bg-pink-500 text-white px-2 py-1 rounded hover:bg-pink-600 text-xs">ユーザー詳細</a>
            </div>
            @endif
        </div>
    </div>
</div>
{{-- 継続依頼LINE送信モーダル --}}
<div id="cont-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm mx-4">
        <h3 class="font-bold text-gray-800 mb-2">継続依頼LINE送信</h3>
        <p class="text-sm text-gray-600 mb-4">
            <span class="font-medium">{{ $application->user?->name }}</span> さんに継続購入のご案内LINEを送信します。
        </p>
        <div class="flex gap-3 justify-end">
            <button type="button"
                    onclick="document.getElementById('cont-modal').classList.add('hidden')"
                    class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded hover:bg-gray-200">
                キャンセル
            </button>
            <form method="POST" action="{{ route('admin.applications.continuation_line', $application) }}">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm text-white bg-green-500 rounded hover:bg-green-600">
                    送信する
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

