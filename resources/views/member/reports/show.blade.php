@extends('layouts.member')

@section('title', '報告詳細')

@section('content')
<div class="py-2">

    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('member.mypage') }}" class="text-pink-500 text-sm">← マイページ</a>
    </div>

    @php
        $badge = match($report->status) {
            'pending'  => ['label' => '承認待ち',  'color' => 'bg-blue-100 text-blue-600'],
            'approved' => ['label' => '支払い待ち', 'color' => 'bg-yellow-100 text-yellow-700'],
            'rejected' => ['label' => '差戻し',    'color' => 'bg-red-100 text-red-600'],
            default    => null,
        };
        if ($report->status === 'approved' && $report->payment_status === 'paid') {
            $badge = ['label' => '支払い済', 'color' => 'bg-teal-100 text-teal-700'];
        }
        $coopFee = $report->purchase_type === 'continuation'
            ? ($report->campaign?->continuation_cooperation_fee ?? 0)
            : ($report->campaign?->cooperation_fee ?? 0);
        $purchaseAmt  = $report->purchase_amount ?? 0;
        $adjustAmt    = $report->adjustment_amount ?? 0;
        $total        = $purchaseAmt + $coopFee + ($report->bonus_amount ?? 0) + $adjustAmt;
        $purchaseTypeLabel = match($report->purchase_type) {
            'initial'      => '初回購入',
            'continuation' => '継続購入',
            'other'        => 'その他',
            default        => $report->purchase_type ?? '-',
        };
    @endphp

    {{-- 案件情報 --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4 flex items-center gap-3">
        <div class="w-12 h-12 bg-pink-50 rounded-lg flex-shrink-0 overflow-hidden">
            @if($report->campaign?->thumbnail)
                <img src="{{ asset('storage/' . $report->campaign->thumbnail) }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center text-xl">💄</div>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-medium text-gray-800 truncate">{{ $report->campaign->title ?? '削除済み案件' }}</p>
            <div class="flex items-center gap-2 mt-1">
                <p class="text-xs text-gray-400">報告：{{ $report->created_at->format('Y/m/d') }}</p>
                @if($badge)
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $badge['color'] }}">{{ $badge['label'] }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- 差戻し理由 --}}
    @if($report->status === 'rejected' && $report->reject_reason)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
        <p class="text-xs font-medium text-red-700 mb-1">差戻し理由</p>
        <p class="text-sm text-red-600">{{ $report->reject_reason }}</p>
    </div>
    @endif

    {{-- 報告内容 --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4">
        <h2 class="font-bold text-gray-700 mb-3 text-sm">報告内容</h2>
        <dl class="grid grid-cols-2 gap-y-3 text-sm">
            <dt class="text-gray-500">報告種別</dt>
            <dd class="font-medium text-gray-800">{{ $purchaseTypeLabel }}</dd>
            <dt class="text-gray-500">モニター経費</dt>
            <dd class="text-gray-800">¥{{ number_format($purchaseAmt) }}</dd>
            @if($report->purchase_type !== 'other')
            <dt class="text-gray-500">モニター協力金</dt>
            <dd class="text-pink-600 font-medium">¥{{ number_format($coopFee) }}</dd>
            @endif
            @if($adjustAmt)
            <dt class="text-gray-500">調整金額</dt>
            <dd class="{{ $adjustAmt > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $adjustAmt > 0 ? '+' : '' }}¥{{ number_format($adjustAmt) }}</dd>
            @endif
            @if($report->purchase_type !== 'other')
            <dt class="text-gray-500 font-medium">合計</dt>
            <dd class="font-bold text-gray-800">¥{{ number_format($total) }}</dd>
            @endif
            @if($report->payment_method)
            @php
                $paymentLabel = match(true) {
                    str_starts_with($report->payment_method, 'other:') => 'その他: ' . substr($report->payment_method, 6),
                    $report->payment_method === 'credit_card' => 'クレジットカード',
                    $report->payment_method === 'cod'         => '代引き',
                    $report->payment_method === 'deferred'    => '後払い',
                    $report->payment_method === 'bank'        => '銀行振込',
                    $report->payment_method === 'none'        => 'お支払い無し',
                    default => $report->payment_method,
                };
            @endphp
            <dt class="text-gray-500">お支払方法</dt>
            <dd class="text-gray-800">{{ $paymentLabel }}</dd>
            @endif
        </dl>
        @if($report->purchase_type === 'other' && $report->report_body)
        <div class="mt-3 pt-3 border-t border-gray-100">
            <p class="text-xs text-gray-500 mb-1">報告内容</p>
            <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $report->report_body }}</p>
        </div>
        @endif
    </div>

    {{-- 報告画像 --}}
    @if($report->images->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4">
        <h2 class="font-bold text-gray-700 mb-3 text-sm">報告画像</h2>
        <div class="grid grid-cols-2 gap-3">
            @foreach($report->images as $image)
            <img src="{{ Storage::url($image->image_path) }}"
                 class="w-full rounded-lg border border-gray-100 cursor-pointer"
                 onclick="openLightbox(this.src)">
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
