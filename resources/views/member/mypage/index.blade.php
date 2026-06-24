@extends('layouts.member')

@section('title', 'マイページ')

@section('content')
<div class="py-2">

    {{-- プロフィール & 残高 --}}
    <div class="bg-gradient-to-r from-pink-500 to-pink-400 text-white rounded-2xl p-5 mb-5 shadow-md">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-white/30 rounded-full flex items-center justify-center text-xl">
                👤
            </div>
            <div>
                <p class="font-bold text-lg">{{ $user->name ?? '未設定' }}</p>
                <p class="text-pink-100 text-xs">{{ $user->name_kana ?? '' }}</p>
            </div>
        </div>
        <div class="bg-white/20 rounded-xl px-4 py-3 text-center">
            <p class="text-pink-100 text-xs mb-1">モニター協力金残高</p>
            <p class="text-3xl font-bold">¥{{ number_format($user->point_balance) }}</p>
        </div>
    </div>

    {{-- アクションボタン --}}
    <div class="grid grid-cols-2 gap-3 mb-5">
        <a href="{{ route('member.reports.create') }}"
           class="bg-pink-500 text-white py-3 rounded-xl text-sm font-medium text-center">
            📋 モニター報告
        </a>
        <a href="{{ route('member.profile.edit') }}"
           class="bg-white border border-gray-200 text-gray-700 py-3 rounded-xl text-sm font-medium text-center">
            ✏️ 情報変更
        </a>
    </div>

    {{-- ログアウト --}}
    <form method="POST" action="{{ route('member.logout') }}" class="mb-5">
        @csrf
        <button type="submit" class="w-full bg-gray-100 text-gray-500 py-3 rounded-xl text-sm font-medium">
            ログアウト
        </button>
    </form>

    {{-- 応募一覧 --}}
    <h2 class="font-bold text-gray-700 mb-3">応募履歴</h2>

    @php
        $totalApplications = collect($groups)->sum(fn($g) => $g->count());
    @endphp

    @if($totalApplications === 0)
        <div class="text-center py-10 text-gray-400">
            <div class="text-3xl mb-2">📋</div>
            <p class="text-sm">まだ応募した案件がありません</p>
            <a href="{{ route('member.campaigns.index') }}"
               class="inline-block mt-3 text-pink-500 text-sm font-medium">
                案件を探す →
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($groups as $label => $apps)
                @if($apps->isNotEmpty())
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2.5 border-b border-gray-100 flex items-center justify-between">
                        <span class="font-semibold text-sm text-gray-700">{{ $label }}</span>
                        <span class="bg-pink-100 text-pink-600 text-xs font-bold px-2 py-0.5 rounded-full">
                            {{ $apps->count() }}件
                        </span>
                    </div>

                    <div class="divide-y divide-gray-50">
                        @foreach($apps as $app)
                        <div class="px-4 py-3 flex items-center gap-3">
                            <div class="w-10 h-10 bg-pink-50 rounded-lg flex-shrink-0 overflow-hidden">
                                @if($app->campaign && $app->campaign->thumbnail)
                                    <img src="{{ asset('storage/' . $app->campaign->thumbnail) }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-lg">💄</div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">
                                    {{ $app->campaign->title ?? '削除済み案件' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    応募：{{ $app->applied_at->format('Y/m/d') }}
                                </p>
                            </div>
                            @if($app->campaign)
                            <a href="{{ route('member.campaigns.show', $app->campaign) }}"
                               class="text-xs text-pink-500 flex-shrink-0">
                                詳細 →
                            </a>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
@endsection
