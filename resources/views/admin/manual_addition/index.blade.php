@extends('layouts.admin')

@section('title', '手動追加')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">手動追加</h1>

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@error('user_id')<div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-4 py-2 rounded mb-4 text-sm">ユーザーを選択してください。</div>@enderror
@error('campaign_id')<div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-4 py-2 rounded mb-4 text-sm">案件を選択してください。</div>@enderror
@error('outcome')<div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-4 py-2 rounded mb-4 text-sm">成果を選択してください。</div>@enderror

{{-- ユーザー検索 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 mb-4">
    <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">ユーザー検索</h2>
    <form method="GET" action="{{ route('admin.manual_addition.index') }}" class="flex gap-2">
        <input type="text" name="q" value="{{ $q }}" placeholder="ユーザーID / LINE名 / 名前 / フリガナ"
               class="flex-1 border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
        <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">検索</button>
    </form>
</div>

{{-- 成果追加フォーム --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
    <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">成果を追加</h2>

    <form method="POST" action="{{ route('admin.manual_addition.store') }}">
        @csrf

        <div class="mb-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">候補ユーザー</p>
            @if($q !== null && $users->isEmpty())
                <p class="text-sm text-gray-400">「{{ $q }}」に一致するユーザーが見つかりませんでした。</p>
            @elseif($users->isEmpty())
                <p class="text-sm text-gray-400">上の検索フォームでユーザーを検索してください。</p>
            @else
                <div class="border dark:border-gray-600 rounded divide-y dark:divide-gray-600 max-h-72 overflow-y-auto">
                    @foreach($users as $user)
                    <label class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                        <input type="radio" name="user_id" value="{{ $user->id }}" required>
                        <span class="text-gray-400 w-28 shrink-0">{{ $user->bimoni_user_id ?? '-' }}</span>
                        <span class="text-gray-500 w-32 shrink-0 truncate">{{ $user->line_display_name ?? '-' }}</span>
                        <span class="font-medium w-28 shrink-0 dark:text-gray-200">{{ $user->name ?? '（未登録）' }}</span>
                        <span class="text-gray-500 dark:text-gray-400">{{ $user->name_kana ?? '-' }}</span>
                    </label>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mb-4 flex flex-wrap gap-3">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">案件ステータス</label>
                <select id="status-select" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1.5 text-sm">
                    <option value="">選択してください</option>
                    <option value="published">公開中</option>
                    <option value="paused">一時停止</option>
                    <option value="closed">終了</option>
                    <option value="draft">下書き</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">案件</label>
                <select id="campaign-select" name="campaign_id" required disabled
                        class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1.5 text-sm min-w-[16rem]">
                    <option value="">まず案件ステータスを選択してください</option>
                    @foreach($campaigns as $campaign)
                    <option value="{{ $campaign->id }}" data-status="{{ $campaign->status }}" hidden disabled>{{ $campaign->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-4">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">成果</p>
            <div class="flex gap-4 text-sm">
                <label class="flex items-center gap-1.5">
                    <input type="radio" name="outcome" value="continuation_ok" required>
                    実施完了（継続OK）
                </label>
                <label class="flex items-center gap-1.5">
                    <input type="radio" name="outcome" value="continuation_ng" required>
                    実施完了（継続NG）
                </label>
            </div>
        </div>

        <button type="submit" class="bg-pink-500 text-white px-6 py-2 rounded hover:bg-pink-600 text-sm">
            成果を追加
        </button>
    </form>
</div>

<script>
(function () {
    var statusSelect   = document.getElementById('status-select');
    var campaignSelect = document.getElementById('campaign-select');
    var options        = Array.from(campaignSelect.querySelectorAll('option[data-status]'));

    statusSelect.addEventListener('change', function () {
        var status = statusSelect.value;
        campaignSelect.value = '';
        options.forEach(function (opt) {
            var matches = opt.dataset.status === status;
            opt.hidden = !matches;
            opt.disabled = !matches;
        });
        campaignSelect.disabled = !status;
    });
})();
</script>
@endsection
