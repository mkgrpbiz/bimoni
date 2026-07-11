@extends('layouts.member')

@section('title', '解約方法一覧')

@section('content')
<div class="py-2">

    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('member.mypage') }}" class="text-pink-500 text-sm">← マイページ</a>
    </div>

    <h1 class="font-bold text-gray-700 mb-3">解約方法一覧</h1>
    <p class="text-xs text-gray-400 mb-4">実施が完了したモニターの解約方法・お問い合わせ先です。</p>

    @if($campaigns->isEmpty())
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 text-center">
            <p class="text-xs text-gray-400">対象の案件がありません</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($campaigns as $campaign)
            <details class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <summary class="px-4 py-3 flex items-center gap-3 cursor-pointer select-none">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-gray-400 mb-0.5">商品名</p>
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $campaign->title }}</p>
                    </div>
                    <span class="text-pink-500 text-xs flex-shrink-0">解約方法を見る</span>
                </summary>
                <div class="px-4 pb-4 pt-1 border-t border-gray-50 space-y-3">
                    @if($campaign->cancellation_info)
                    <div>
                        <p class="text-xs font-bold text-gray-500 mb-1">解約方法</p>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $campaign->cancellation_info }}</p>
                    </div>
                    @endif
                    @if($campaign->cancellation_phone)
                    <div>
                        <p class="text-xs font-bold text-gray-500 mb-1">電話番号</p>
                        <a href="tel:{{ preg_replace('/[^\d]/', '', $campaign->cancellation_phone) }}" class="text-sm text-pink-600 font-medium">{{ $campaign->cancellation_phone }}</a>
                        @if($campaign->cancellation_hours)
                        <p class="text-xs text-gray-400 mt-0.5">受付時間／{{ $campaign->cancellation_hours }}</p>
                        @endif
                    </div>
                    @endif
                    @if($campaign->cancellation_mypage_url)
                    <div>
                        <p class="text-xs font-bold text-gray-500 mb-1">マイページ</p>
                        <a href="{{ $campaign->cancellation_mypage_url }}" target="_blank" rel="noopener" class="text-sm text-pink-600 break-all underline">{{ $campaign->cancellation_mypage_url }}</a>
                    </div>
                    @endif
                    @if($campaign->cancellation_email)
                    <div>
                        <p class="text-xs font-bold text-gray-500 mb-1">メールアドレス</p>
                        <a href="mailto:{{ $campaign->cancellation_email }}" class="text-sm text-pink-600 break-all">{{ $campaign->cancellation_email }}</a>
                    </div>
                    @endif
                </div>
            </details>
            @endforeach
        </div>
    @endif

</div>
@endsection
