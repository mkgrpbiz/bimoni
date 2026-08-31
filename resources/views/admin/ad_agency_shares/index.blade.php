@extends('layouts.admin')

@section('title', '広告代理店共有管理')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">広告代理店共有管理</h1>
    <a href="{{ route('admin.ad_agency_shares.create') }}"
       class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">
        ＋ アカウント追加
    </a>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 mb-5">
    <p class="text-sm font-bold text-gray-700 dark:text-gray-200 mb-1">共有用の管理画面URL</p>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">下記URLと、追加したアカウントのメールアドレス・パスワードを広告代理店に共有してください。</p>
    <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 border dark:border-gray-600 rounded-lg px-3 py-2.5">
        <input type="text" id="share-url-input" value="{{ $shareUrl }}" readonly
               class="flex-1 min-w-0 bg-transparent text-sm text-gray-700 dark:text-gray-200 outline-none">
        <button type="button" onclick="copyShareUrl()"
                class="shrink-0 bg-pink-500 text-white text-xs px-3 py-1.5 rounded hover:bg-pink-600">
            コピー
        </button>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
            <tr>
                <th class="px-4 py-2 text-left">名前</th>
                <th class="px-4 py-2 text-left">メールアドレス</th>
                <th class="px-4 py-2 text-left">追加日</th>
                <th class="px-4 py-2 text-left">最終ログイン</th>
                <th class="px-4 py-2 text-left">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($shareUsers as $su)
            <tr class="even:bg-gray-50 hover:bg-gray-100 dark:hover:bg-gray-750">
                <td class="px-4 py-2 font-medium dark:text-gray-200">{{ $su->name }}</td>
                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $su->email }}</td>
                <td class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">{{ $su->created_at->format('Y/m/d') }}</td>
                <td class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400">{{ $su->last_login_at?->format('Y/m/d H:i') ?? '未ログイン' }}</td>
                <td class="px-4 py-2">
                    <form method="POST" action="{{ route('admin.ad_agency_shares.destroy', $su) }}">
                        @csrf @method('DELETE')
                        <button type="submit"
                                onclick="return confirm('削除しますか？このアカウントはログインできなくなります。')"
                                class="bg-red-500 text-white px-2 py-1 rounded text-xs hover:bg-red-600">
                            削除
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-700 dark:text-gray-500">共有アカウントがありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
function copyShareUrl() {
    const input = document.getElementById('share-url-input');
    try {
        input.focus();
        input.setSelectionRange(0, input.value.length);
        document.execCommand('copy');
    } catch (e) {
        if (navigator.clipboard) navigator.clipboard.writeText(input.value).catch(() => {});
    }
    alert('コピーしました');
}
</script>
@endsection
