@extends('layouts.admin')

@section('title', 'ポイント管理')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">ポイント管理</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

    {{-- 手動調整フォーム --}}
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-4">手動ポイント調整</h2>
            @if(session('success'))
                <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-3 py-2 rounded mb-3 text-sm">{{ session('success') }}</div>
            @endif
            <form method="POST" action="{{ route('admin.points.adjust') }}">
                @csrf
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">モニター</label>
                    <select name="user_id" required class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
                        <option value="">選択してください</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}（{{ $user->point_balance }}pt）</option>
                        @endforeach
                    </select>
                    @error('user_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">金額（マイナスも可）</label>
                    <input type="number" name="amount" required placeholder="例: 500 または -200"
                           class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm @error('amount') border-red-400 @enderror">
                    @error('amount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">理由</label>
                    <input type="text" name="reason" required placeholder="調整理由"
                           class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm @error('reason') border-red-400 @enderror">
                    @error('reason')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="w-full bg-pink-600 text-white py-2 rounded hover:bg-pink-700 text-sm">調整を実行</button>
            </form>
        </div>
    </div>

    {{-- ポイント履歴 --}}
    <div class="lg:col-span-2">
        <form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-3 mb-3 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">モニター</label>
                <select name="user_id" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
                    <option value="">すべて</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">種別</label>
                <select name="type" class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-1 text-sm">
                    <option value="">すべて</option>
                    <option value="earn"     @selected(request('type') === 'earn')>獲得</option>
                    <option value="exchange" @selected(request('type') === 'exchange')>交換</option>
                    <option value="adjust"   @selected(request('type') === 'adjust')>調整</option>
                    <option value="cancel"   @selected(request('type') === 'cancel')>取消</option>
                </select>
            </div>
            <button type="submit" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">絞り込み</button>
        </form>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left">日時</th>
                        <th class="px-4 py-3 text-left">モニター</th>
                        <th class="px-4 py-3 text-left">種別</th>
                        <th class="px-4 py-3 text-right">金額</th>
                        <th class="px-4 py-3 text-left">理由</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @forelse($points as $point)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $point->created_at->format('Y/m/d') }}</td>
                        <td class="px-4 py-3 dark:text-gray-200">{{ $point->user->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-1.5 py-0.5 rounded
                                {{ $point->type === 'earn' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                                {{ $point->getTypeLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-medium {{ $point->amount > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                            {{ $point->amount > 0 ? '+' : '' }}{{ number_format($point->amount) }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">{{ $point->reason ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">履歴がありません</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $points->links() }}</div>
    </div>
</div>
@endsection
