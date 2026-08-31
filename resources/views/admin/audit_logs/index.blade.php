@extends('layouts.admin')

@section('title', '監査ログ')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">監査ログ</h1>

<p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
    管理画面にログインした管理者が行った作成・更新・削除を記録しています（会員ページやcron処理による自動更新は対象外）。
</p>

<form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">管理者</label>
        <select name="admin_id" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1.5 text-sm">
            <option value="">すべて</option>
            @foreach($admins as $adm)
                <option value="{{ $adm->id }}" @selected(request('admin_id') == $adm->id)>{{ $adm->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">対象種別</label>
        <select name="model" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1.5 text-sm">
            <option value="">すべて</option>
            @foreach($models as $m)
                <option value="{{ $m }}" @selected(request('model') === $m)>{{ \App\Models\AuditLog::modelLabel($m) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">操作</label>
        <select name="action" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1.5 text-sm">
            <option value="">すべて</option>
            <option value="created" @selected(request('action') === 'created')>作成</option>
            <option value="updated" @selected(request('action') === 'updated')>更新</option>
            <option value="deleted" @selected(request('action') === 'deleted')>削除</option>
        </select>
    </div>
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">期間（から）</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1.5 text-sm">
    </div>
    <div>
        <label class="block text-xs text-gray-700 dark:text-gray-400 mb-1">期間（まで）</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1.5 text-sm">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-3 py-1.5 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.audit_logs.index') }}" class="text-sm text-gray-500 hover:text-gray-700 py-1.5">リセット</a>
</form>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs">
            <tr>
                <th class="px-4 py-2 text-left whitespace-nowrap">発生日時</th>
                <th class="px-4 py-2 text-left whitespace-nowrap">管理者</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">操作</th>
                <th class="px-3 py-2 text-left whitespace-nowrap">対象</th>
                <th class="px-4 py-2 text-left">変更内容</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($logs as $log)
            <tr class="even:bg-gray-50 hover:bg-gray-100 dark:hover:bg-gray-750 align-top">
                <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-400 whitespace-nowrap">
                    {{ $log->created_at->format('Y/m/d H:i:s') }}
                </td>
                <td class="px-4 py-2 text-gray-800 dark:text-gray-200 whitespace-nowrap">
                    {{ $log->admin?->name ?? $log->admin_name ?? '（削除済み）' }}
                </td>
                <td class="px-3 py-2">
                    <span class="text-xs px-2 py-0.5 rounded
                        {{ match($log->action) {
                            'created' => 'bg-green-100 text-green-700',
                            'updated' => 'bg-yellow-100 text-yellow-700',
                            'deleted' => 'bg-red-100 text-red-700',
                            default   => 'bg-gray-100 text-gray-600',
                        } }}">
                        {{ match($log->action) { 'created' => '作成', 'updated' => '更新', 'deleted' => '削除', default => $log->action } }}
                    </span>
                </td>
                <td class="px-3 py-2 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                    {{ $log->getModelLabel() }}
                    <span class="text-xs text-gray-400">#{{ $log->model_id }}</span>
                    @if($log->label)
                        <div class="text-xs text-gray-500 dark:text-gray-400 max-w-[16rem] truncate">{{ $log->label }}</div>
                    @endif
                </td>
                <td class="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                    @if($log->action === 'updated' && $log->changes)
                        <ul class="space-y-0.5">
                            @foreach($log->changes as $field => $diff)
                            <li>
                                <span class="font-mono text-gray-500 dark:text-gray-500">{{ $field }}</span>:
                                <span class="text-red-500">{{ Str::limit((string) ($diff['from'] ?? ''), 30) ?: '(空)' }}</span>
                                →
                                <span class="text-green-600">{{ Str::limit((string) ($diff['to'] ?? ''), 30) ?: '(空)' }}</span>
                            </li>
                            @endforeach
                        </ul>
                    @elseif($log->changes)
                        <details>
                            <summary class="cursor-pointer select-none text-gray-500">{{ count($log->changes) }}項目</summary>
                            <ul class="space-y-0.5 mt-1">
                                @foreach($log->changes as $field => $value)
                                <li>
                                    <span class="font-mono text-gray-500 dark:text-gray-500">{{ $field }}</span>:
                                    {{ Str::limit(is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value, 40) }}
                                </li>
                                @endforeach
                            </ul>
                        </details>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-700 dark:text-gray-500">ログがありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $logs->links() }}</div>
@endsection
