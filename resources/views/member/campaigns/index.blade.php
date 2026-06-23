@extends('layouts.member')

@section('title', '案件一覧')

@section('content')
<div class="py-2">
    <h1 class="text-lg font-bold text-gray-800 mb-4">案件一覧</h1>

    @if($campaigns->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <div class="text-4xl mb-3">🔍</div>
            <p class="text-sm">現在公開中の案件はありません</p>
        </div>
    @else
    <div class="grid grid-cols-2 gap-3">
        @foreach($campaigns as $campaign)
        @php $appliedStatus = $appliedIds->get($campaign->id); @endphp
        <a href="{{ route('member.campaigns.show', $campaign) }}"
           class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 active:opacity-70 transition-opacity">

            {{-- サムネイル --}}
            <div class="aspect-square bg-gradient-to-br from-pink-100 to-pink-200 relative overflow-hidden">
                @if($campaign->thumbnail)
                    <img src="{{ asset('storage/' . $campaign->thumbnail) }}"
                         alt="{{ $campaign->title }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-4xl">💄</div>
                @endif

                @if($appliedStatus)
                    <div class="absolute top-2 right-2 bg-gray-700 text-white text-xs px-2 py-0.5 rounded-full">
                        応募済み
                    </div>
                @endif
            </div>

            {{-- 情報 --}}
            <div class="p-3">
                <p class="text-xs font-medium text-gray-800 leading-snug line-clamp-2 mb-2">
                    {{ $campaign->title }}
                </p>
                <p class="text-pink-600 font-bold text-sm">
                    ¥{{ number_format($campaign->cooperation_fee) }}
                </p>
                <p class="text-gray-400 text-xs">モニター協力金</p>

                <div class="mt-2.5">
                    @if($appliedStatus)
                        <div class="w-full bg-gray-100 text-gray-500 text-xs py-2 rounded-lg text-center font-medium">
                            応募済み
                        </div>
                    @else
                        <div class="w-full bg-pink-500 text-white text-xs py-2 rounded-lg text-center font-medium">
                            応募する
                        </div>
                    @endif
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>
@endsection
