@extends('layouts.admin')

@section('title', $campaign->title . ' 日別件数管理')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.campaigns.applications', $campaign) }}" class="text-gray-400 hover:text-gray-600">← 応募者一覧</a>
    <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ $campaign->title }} 日別件数管理</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    {{-- 左: 件数一覧 --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left">日付</th>
                        <th class="px-4 py-3 text-center">目標</th>
                        <th class="px-4 py-3 text-center">打診済</th>
                        <th class="px-4 py-3 text-center">予約済</th>
                        <th class="px-4 py-3 text-center">実施完了</th>
                        <th class="px-4 py-3 text-left">メモ</th>
                        <th class="px-4 py-3 text-left">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @forelse($slots as $slot)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-2 font-medium">{{ $slot->target_date->format('Y/m/d (D)') }}</td>
                        <td class="px-4 py-2 text-center font-bold text-gray-800 dark:text-gray-100">{{ $slot->planned_count }}</td>
                        <td class="px-4 py-2 text-center text-purple-600">{{ $slot->invited_count }}</td>
                        <td class="px-4 py-2 text-center text-indigo-600">{{ $slot->reserved_count }}</td>
                        <td class="px-4 py-2 text-center text-teal-600">{{ $slot->completed_count }}</td>
                        <td class="px-4 py-2 text-gray-400 text-xs">{{ $slot->memo ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <div class="flex gap-2">
                                <button onclick="openEdit({{ $slot->id }}, '{{ $slot->target_date->format('Y-m-d') }}', {{ $slot->planned_count }}, '{{ addslashes($slot->memo ?? '') }}')"
                                        class="text-xs text-blue-600 hover:underline">編集</button>
                                <form method="POST" action="{{ route('admin.campaigns.daily_slots.destroy', [$campaign, $slot]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" onclick="return confirm('削除しますか？')"
                                            class="text-xs text-red-500 hover:underline">削除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">登録がありません</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $slots->links() }}</div>
    </div>

    {{-- 右: 入力フォーム --}}
    <div class="space-y-4">
        {{-- 新規追加 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3 text-sm">1件追加</h2>
            <form method="POST" action="{{ route('admin.campaigns.daily_slots.store', $campaign) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs text-gray-500 mb-1">日付</label>
                    <input type="date" name="target_date" required
                           class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">目標件数</label>
                    <input type="number" name="planned_count" min="0" value="0" required
                           class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">メモ</label>
                    <input type="text" name="memo"
                           class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-1.5 rounded text-sm hover:bg-blue-700">追加</button>
            </form>
        </div>

        {{-- CSV インポート --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-2 text-sm">CSVインポート</h2>
            <p class="text-xs text-gray-400 mb-3">形式: <code>6/24,10</code>（月/日,件数）<br>同じ日付は上書きされます。</p>
            <form method="POST" action="{{ route('admin.campaigns.daily_slots.import', $campaign) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <input type="file" name="csv_file" accept=".csv,.txt" required
                           class="w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                <button type="submit" class="w-full bg-green-600 text-white py-1.5 rounded text-sm hover:bg-green-700">インポート</button>
            </form>
        </div>
    </div>
</div>

{{-- 編集モーダル --}}
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-80">
        <h3 class="font-bold mb-4 text-gray-800 dark:text-gray-100">件数編集</h3>
        <form id="editForm" method="POST" class="space-y-3">
            @csrf @method('PATCH')
            <div>
                <label class="block text-xs text-gray-500 mb-1">日付</label>
                <input id="editDate" type="text" readonly
                       class="w-full border rounded px-2 py-1 text-sm bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">目標件数</label>
                <input id="editCount" type="number" name="planned_count" min="0" required
                       class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">メモ</label>
                <input id="editMemo" type="text" name="memo"
                       class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-1.5 rounded text-sm hover:bg-blue-700">保存</button>
                <button type="button" onclick="closeEdit()" class="flex-1 bg-gray-200 text-gray-700 py-1.5 rounded text-sm hover:bg-gray-300">閉じる</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, date, count, memo) {
    document.getElementById('editDate').value = date;
    document.getElementById('editCount').value = count;
    document.getElementById('editMemo').value = memo;
    document.getElementById('editForm').action = '{{ route('admin.campaigns.daily_slots.index', $campaign) }}/' + id;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEdit() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>
@endsection
