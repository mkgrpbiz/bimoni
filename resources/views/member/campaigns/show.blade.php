@extends('layouts.member')

@section('title', $campaign->title)

@section('content')
<div class="py-2">

    {{-- 画像 --}}
    <div class="w-full aspect-video bg-gradient-to-br from-pink-100 to-pink-200 rounded-xl overflow-hidden mb-4">
        @if($campaign->thumbnail)
            <img src="{{ asset('storage/' . $campaign->thumbnail) }}"
                 alt="{{ $campaign->title }}"
                 class="w-full h-full object-cover">
        @else
            <div class="w-full h-full flex items-center justify-center text-6xl">💄</div>
        @endif
    </div>

    {{-- 基本情報 --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4">
        <h1 class="text-base font-bold text-gray-800 mb-3">{{ $campaign->title }}</h1>

        <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <span class="text-sm text-gray-500">商品金額</span>
            <span class="text-sm font-medium text-gray-800">
                {{ $campaign->product_price ? '¥' . number_format($campaign->product_price) : '-' }}
            </span>
        </div>
        <div class="flex items-center justify-between py-2 border-b border-gray-100">
            <span class="text-sm text-gray-500">モニター協力金</span>
            <span class="text-lg font-bold text-pink-600">¥{{ number_format($campaign->cooperation_fee) }}</span>
        </div>
        @if($campaign->category)
        <div class="flex items-center justify-between py-2">
            <span class="text-sm text-gray-500">カテゴリ</span>
            <span class="text-sm text-gray-700">{{ $campaign->category->name }}</span>
        </div>
        @endif
    </div>

    {{-- 案件内容 --}}
    @if($campaign->description)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4">
        <h2 class="text-sm font-bold text-gray-700 mb-2">案件内容</h2>
        <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap">{{ $campaign->description }}</p>
    </div>
    @endif

    {{-- 応募条件 --}}
    @if($campaign->requirements)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4">
        <h2 class="text-sm font-bold text-gray-700 mb-2">応募条件</h2>
        <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap">{{ $campaign->requirements }}</p>
    </div>
    @endif

    {{-- 注意事項 --}}
    @if($campaign->notes)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
        <h2 class="text-sm font-bold text-amber-700 mb-2">⚠ 注意事項</h2>
        <p class="text-sm text-amber-700 leading-relaxed whitespace-pre-wrap">{{ $campaign->notes }}</p>
    </div>
    @endif

    {{-- 応募ボタン --}}
    <div class="pb-8">
        @if($application)
            <div class="w-full bg-gray-100 text-gray-500 py-4 rounded-xl text-center font-bold">
                応募済みです
            </div>
            <p class="text-xs text-gray-400 text-center mt-2">
                ステータス：{{ $application->status }}
            </p>
        @else
            <form method="POST" action="{{ route('member.campaigns.apply', $campaign) }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('この案件に応募しますか？')"
                        class="w-full bg-pink-500 text-white py-4 rounded-xl font-bold text-base shadow-md hover:bg-pink-600 active:bg-pink-700">
                    この案件に応募する
                </button>
            </form>
        @endif
    </div>

    <a href="{{ route('member.campaigns.index') }}"
       class="block text-center text-sm text-gray-400 mt-2 mb-8">
        ← 案件一覧に戻る
    </a>
</div>
@endsection
