@extends('layouts.member')

@section('title', '報告詳細')

@section('content')
<div class="py-2">

    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('member.mypage') }}" class="text-pink-500 text-sm">← マイページ</a>
    </div>

    @php
        $badge = match($report->status) {
            'pending'  => ['label' => '承認待ち', 'color' => 'bg-yellow-100 text-yellow-700'],
            'approved' => ['label' => '承認',     'color' => 'bg-green-100 text-green-700'],
            'rejected' => ['label' => '差戻し',   'color' => 'bg-red-100 text-red-700'],
            default    => null,
        };
        $adjustAmt = $report->adjustment_amount ?? 0;
        $total     = $report->cooperation_fee + $adjustAmt;
    @endphp

    {{-- 案件情報 --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4 flex items-center gap-3">
        <div class="w-12 h-12 bg-blue-50 rounded-lg flex-shrink-0 flex items-center justify-center text-xl">📦</div>
        <div class="flex-1 min-w-0">
            <p class="font-medium text-gray-800 truncate">回収サービス（{{ $report->item_count }}点）</p>
            <div class="flex items-center gap-2 mt-1">
                <p class="text-xs text-gray-400">申請：{{ $report->created_at->format('Y/m/d') }}</p>
                @if($badge)
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $badge['color'] }}">{{ $badge['label'] }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- 差戻し理由 --}}
    @if($report->status === 'rejected' && $report->rejection_reason)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
        <p class="text-xs font-medium text-red-700 mb-1">差戻し理由</p>
        <p class="text-sm text-red-600">{{ $report->rejection_reason }}</p>
    </div>
    @endif

    {{-- 報告内容 --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4">
        <h2 class="font-bold text-gray-700 mb-3 text-sm">報告内容</h2>

        @if($campaigns->isNotEmpty())
        <div class="mb-3 pb-3 border-b border-gray-100">
            <p class="text-xs text-gray-500 mb-1">対象案件</p>
            @foreach($campaigns as $c)
            <p class="text-sm text-gray-800">・{{ $c->title }}</p>
            @endforeach
        </div>
        @endif

        <dl class="grid grid-cols-2 gap-y-3 text-sm">
            <dt class="text-gray-500">商品数</dt>
            <dd class="text-gray-800">{{ $report->item_count }}点</dd>
            <dt class="text-gray-500">送料</dt>
            <dd class="text-gray-800">¥{{ number_format($report->shipping_fee) }}</dd>
            <dt class="text-gray-500">追跡番号</dt>
            <dd class="text-gray-800 font-mono text-xs">{{ $report->tracking_number ?? '-' }}</dd>
            <dt class="text-gray-500">到着予定日</dt>
            <dd class="text-gray-800">{{ $report->estimated_arrival_date?->format('Y/m/d') ?? '-' }}</dd>
            <dt class="text-gray-500">協力金</dt>
            <dd class="text-pink-600 font-medium">¥{{ number_format($report->cooperation_fee) }}</dd>
            @if($adjustAmt)
            <dt class="text-gray-500">調整金額</dt>
            <dd class="{{ $adjustAmt > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $adjustAmt > 0 ? '+' : '' }}¥{{ number_format($adjustAmt) }}</dd>
            @if($report->adjustment_reason)
            <dt class="text-gray-500">調整理由</dt>
            <dd class="text-gray-800">{{ $report->adjustment_reason }}</dd>
            @endif
            <dt class="text-gray-500 font-medium">合計</dt>
            <dd class="font-bold text-gray-800">¥{{ number_format($total) }}</dd>
            @endif
        </dl>
    </div>

    {{-- 添付画像 --}}
    @if($report->box_image || $report->label_image)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4">
        <h2 class="font-bold text-gray-700 mb-3 text-sm">添付画像</h2>
        <div class="grid grid-cols-2 gap-3">
            @if($report->box_image)
            <div>
                <p class="text-xs text-gray-500 mb-1">段ボール</p>
                <img src="{{ asset('storage/' . $report->box_image) }}"
                     class="w-full rounded-lg border border-gray-100 cursor-pointer"
                     onclick="openLightbox(this.src)">
            </div>
            @endif
            @if($report->label_image)
            <div>
                <p class="text-xs text-gray-500 mb-1">発送伝票</p>
                <img src="{{ asset('storage/' . $report->label_image) }}"
                     class="w-full rounded-lg border border-gray-100 cursor-pointer"
                     onclick="openLightbox(this.src)">
            </div>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection
