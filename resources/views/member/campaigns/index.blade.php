@extends('layouts.member')

@section('title', '案件一覧')

@section('content')
<div class="py-2">

    {{-- 期間限定キャンペーン --}}
    @if($activeBonuses->isNotEmpty())
    <div class="mb-5">
        <div class="flex items-center gap-2 mb-3">
            <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full animate-pulse">期間限定</span>
            <h2 class="text-sm font-bold text-gray-800">キャンペーン実施中</h2>
        </div>
        <div class="grid grid-cols-2 gap-3">
            @foreach($activeBonuses as $bonus)
            @php
                $campaign = $bonus->campaign;
                $appliedStatus = $appliedIds->get($campaign->id);
            @endphp
            <a href="{{ route('member.campaigns.show', $campaign) }}"
               class="bg-white rounded-xl shadow-sm overflow-hidden border-2 border-red-400 active:opacity-70 transition-opacity">

                <div class="aspect-square bg-gradient-to-br from-pink-100 to-pink-200 relative overflow-hidden">
                    @if($campaign->thumbnail)
                        <img src="{{ asset('storage/' . $campaign->thumbnail) }}"
                             alt="{{ $campaign->title }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-4xl">💄</div>
                    @endif
                    @if($appliedStatus)
                        <div class="absolute top-2 right-2 bg-gray-700 text-white text-xs px-2 py-0.5 rounded-full">応募済み</div>
                    @endif
                    <div class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                        +{{ number_format($bonus->bonus_amount) }}円
                    </div>
                </div>

                <div class="p-3">
                    <p class="text-xs font-medium text-gray-800 leading-snug line-clamp-2 mb-2">{{ $campaign->title }}</p>
                    <p class="text-pink-600 font-bold text-sm">
                        ¥{{ number_format($campaign->cooperation_fee) }}
                        <span class="text-red-500 text-xs font-bold ml-1">+{{ number_format($bonus->bonus_amount) }}円</span>
                    </p>
                    <p class="text-gray-400 text-xs">モニター協力金</p>
                    <p class="text-red-400 text-xs mt-1">
                        ※期間内の応募が対象<br>
                        対象期間 {{ $bonus->start_at->format('n/j H:i') }}〜{{ $bonus->end_at->format('n/j H:i') }}
                    </p>
                    <div class="mt-2.5">
                        @if($appliedStatus)
                            <div class="w-full bg-gray-100 text-gray-500 text-xs py-2 rounded-lg text-center font-medium">応募済み</div>
                        @else
                            <div class="w-full bg-red-500 text-white text-xs py-2 rounded-lg text-center font-medium">応募する</div>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    <hr class="border-gray-200 mb-5">
    @endif

    <h1 class="text-lg font-bold text-gray-800 mb-4">案件一覧</h1>

    @if($campaigns->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <div class="text-4xl mb-3">🔍</div>
            <p class="text-sm">現在公開中の案件はありません</p>
        </div>
    @else
    <div class="grid grid-cols-2 gap-3">
        @foreach($campaigns as $campaign)
        @php
            $appliedStatus = $appliedIds->get($campaign->id);
            $bonus = $activeBonuses->get($campaign->id);
        @endphp
        <a href="{{ route('member.campaigns.show', $campaign) }}"
           class="bg-white rounded-xl shadow-sm overflow-hidden border {{ $bonus ? 'border-red-300' : 'border-gray-100' }} active:opacity-70 transition-opacity">

            <div class="aspect-square bg-gradient-to-br from-pink-100 to-pink-200 relative overflow-hidden">
                @if($campaign->thumbnail)
                    <img src="{{ asset('storage/' . $campaign->thumbnail) }}"
                         alt="{{ $campaign->title }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-4xl">💄</div>
                @endif
                @if($appliedStatus)
                    <div class="absolute top-2 right-2 bg-gray-700 text-white text-xs px-2 py-0.5 rounded-full">応募済み</div>
                @endif
                @if($bonus)
                    <div class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">
                        +{{ number_format($bonus->bonus_amount) }}円
                    </div>
                @endif
            </div>

            <div class="p-3">
                <p class="text-xs font-medium text-gray-800 leading-snug line-clamp-2 mb-2">{{ $campaign->title }}</p>
                <p class="text-pink-600 font-bold text-sm">
                    ¥{{ number_format($campaign->cooperation_fee) }}
                    @if($bonus)
                        <span class="text-red-500 text-xs font-bold ml-1">+{{ number_format($bonus->bonus_amount) }}円</span>
                    @endif
                </p>
                <p class="text-gray-400 text-xs">モニター協力金</p>
                <div class="mt-2.5">
                    @if($appliedStatus)
                        <div class="w-full bg-gray-100 text-gray-500 text-xs py-2 rounded-lg text-center font-medium">応募済み</div>
                    @else
                        <div class="w-full bg-pink-500 text-white text-xs py-2 rounded-lg text-center font-medium">応募する</div>
                    @endif
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>
@endsection
