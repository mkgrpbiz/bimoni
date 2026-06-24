@extends('layouts.admin')

@section('title', 'データインポート')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">データインポート</h1>

@if(session('error'))
    <div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-4 py-2 rounded mb-4 text-sm">{{ session('error') }}</div>
@endif

@if(session('import_result'))
    @php $r = session('import_result'); @endphp
    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-4">
        <p class="font-bold text-blue-800 dark:text-blue-300 mb-2">{{ session('import_type') }}インポート結果</p>
        <p class="text-sm text-blue-700 dark:text-blue-300">✓ 成功：{{ $r['success'] }}件　スキップ：{{ $r['skipped'] }}件</p>
        @if(!empty($r['errors']))
            <div class="mt-2">
                <p class="text-sm font-medium text-red-600 dark:text-red-400">エラー（{{ count($r['errors']) }}件）</p>
                <ul class="text-xs text-red-500 dark:text-red-400 mt-1 space-y-0.5 max-h-32 overflow-y-auto">
                    @foreach($r['errors'] as $err)
                        <li>・{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif

<div class="space-y-4">

    {{-- ユーザーインポート --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-1">ユーザーインポート</h2>
        <p class="text-xs text-gray-700 dark:text-gray-400 mb-3">
            CSVフォーマット：<code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">erme_respondent_id, name, name_kana, gender, birthdate, area, available_times, wants_continuation, point_balance</code>
        </p>
        <form method="POST" action="{{ route('admin.import.users') }}" enctype="multipart/form-data" class="flex gap-3 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CSVファイル</label>
                <input type="file" name="csv_file" accept=".csv,.txt" required
                       class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <button type="submit"
                    onclick="return confirm('ユーザーCSVをインポートしますか？')"
                    class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 text-sm">
                インポート実行
            </button>
        </form>
    </div>

    {{-- 応募履歴インポート --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-1">応募履歴インポート</h2>
        <p class="text-xs text-gray-700 dark:text-gray-400 mb-3">
            CSVフォーマット：<code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">erme_respondent_id, campaign_name, status, applied_at, selected_at, completed_at, approved_at</code>
        </p>
        <p class="text-xs text-yellow-600 dark:text-yellow-400 mb-3">⚠ ユーザーインポートと案件登録が先に完了している必要があります。</p>
        <form method="POST" action="{{ route('admin.import.applications') }}" enctype="multipart/form-data" class="flex gap-3 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CSVファイル</label>
                <input type="file" name="csv_file" accept=".csv,.txt" required
                       class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <button type="submit"
                    onclick="return confirm('応募履歴CSVをインポートしますか？')"
                    class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 text-sm">
                インポート実行
            </button>
        </form>
    </div>

    {{-- ポイント履歴インポート --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-1">ポイント履歴インポート</h2>
        <p class="text-xs text-gray-700 dark:text-gray-400 mb-3">
            CSVフォーマット：<code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">erme_respondent_id, type, amount, reason, granted_at</code>
        </p>
        <form method="POST" action="{{ route('admin.import.points') }}" enctype="multipart/form-data" class="flex gap-3 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CSVファイル</label>
                <input type="file" name="csv_file" accept=".csv,.txt" required
                       class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <button type="submit"
                    onclick="return confirm('ポイント履歴CSVをインポートしますか？')"
                    class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 text-sm">
                インポート実行
            </button>
        </form>
    </div>

</div>
@endsection

