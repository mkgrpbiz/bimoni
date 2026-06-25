@extends('layouts.admin')

@section('title', 'キャンペーン管理')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">キャンペーン管理</h1>

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- 新規登録 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 mb-6">
    <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-4">新規キャンペーン登録</h2>
    <form method="POST" action="{{ route('admin.campaign_bonuses.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">対象案件 <span class="text-red-500">*</span></label>
            <select name="campaign_id" required class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
                <option value="">選択してください</option>
                @foreach($campaigns as $campaign)
                    <option value="{{ $campaign->id }}">{{ $campaign->title }}</option>
                @endforeach
            </select>
            @error('campaign_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">キャンペーン金額 <span class="text-red-500">*</span></label>
            <select name="bonus_amount" required class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
                <option value="300">+300円</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">開始日時 <span class="text-red-500">*</span></label>
            <input type="datetime-local" name="start_at" required
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
            @error('start_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">終了日時 <span class="text-red-500">*</span></label>
            <input type="datetime-local" name="end_at" required
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
            @error('end_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="md:col-span-4 flex justify-end">
            <button type="submit" class="bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 text-sm">
                キャンペーン登録
            </button>
        </div>
    </form>
</div>

{{-- キャンペーン履歴 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <div class="px-5 py-3 border-b dark:border-gray-700">
        <h2 class="font-bold text-gray-700 dark:text-gray-200">キャンペーン履歴</h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-2 text-left">案件名</th>
                <th class="px-4 py-2 text-center">金額</th>
                <th class="px-4 py-2 text-center">期間</th>
                <th class="px-4 py-2 text-center">状態</th>
                <th class="px-4 py-2 text-center">応募数</th>
                <th class="px-4 py-2 text-center">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($bonuses as $bonus)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $bonus->campaign->title }}</td>
                    <td class="px-4 py-3 text-center text-pink-600 dark:text-pink-400 font-bold">+{{ number_format($bonus->bonus_amount) }}円</td>
                    <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400 text-xs">
                        {{ $bonus->start_at->format('Y/m/d H:i') }} 〜 {{ $bonus->end_at->format('Y/m/d H:i') }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($bonus->isActive())
                            <span class="bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 text-xs px-2 py-0.5 rounded-full font-bold">実施中</span>
                        @elseif($bonus->end_at->isPast())
                            <span class="bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 text-xs px-2 py-0.5 rounded-full">終了</span>
                        @else
                            <span class="bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300 text-xs px-2 py-0.5 rounded-full">予定</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">{{ $bonus->applicationsCount() }}件</td>
                    <td class="px-4 py-3 text-center">
                        <form method="POST" action="{{ route('admin.campaign_bonuses.destroy', $bonus) }}"
                              onsubmit="return confirm('このキャンペーンを削除しますか？')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs">削除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">キャンペーンはまだありません</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
