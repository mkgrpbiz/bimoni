@extends('layouts.admin')

@section('title', '報告詳細')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.reports.index') }}"
       class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">← 報告一覧</a>
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">報告詳細</h1>
    <span class="px-2 py-0.5 rounded text-xs {{ $report->getStatusColor() }}">{{ $report->getStatusLabel() }}</span>
</div>

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <div class="lg:col-span-2 space-y-4">

        {{-- 報告詳細 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">報告詳細</h2>
            @php
                $purchaseTypeLabel = match($report->purchase_type) {
                    'initial'      => '初回購入',
                    'continuation' => '継続購入',
                    'other'        => 'その他',
                    default        => $report->purchase_type ?? '-',
                };
                $paymentLabel = match(true) {
                    str_starts_with($report->payment_method ?? '', 'other:') => 'その他: ' . substr($report->payment_method, 6),
                    $report->payment_method === 'credit_card' => 'クレジットカード',
                    $report->payment_method === 'cod'         => '代引き',
                    $report->payment_method === 'deferred'    => '後払い',
                    $report->payment_method === 'bank'        => '銀行振込',
                    $report->payment_method === 'none'        => 'お支払い無し',
                    default => $report->payment_method ?? '-',
                };
                $coopFee = $report->campaign?->cooperation_fee ?? 0;
                $purchaseAmt = $report->purchase_amount ?? 0;
            @endphp
            <dl class="grid grid-cols-2 gap-y-3 text-sm">
                <dt class="text-gray-500 dark:text-gray-400">BIMONI ID</dt>
                <dd class="font-medium dark:text-gray-200">{{ $report->user?->bimoni_user_id ?? '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">名前</dt>
                <dd class="dark:text-gray-200">{{ $report->user?->name ?? '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">フリガナ</dt>
                <dd class="dark:text-gray-200">{{ $report->user?->name_kana ?? '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">報告種別</dt>
                <dd class="font-medium dark:text-gray-200">{{ $purchaseTypeLabel }}</dd>
                @if($report->purchase_type !== 'other')
                <dt class="text-gray-500 dark:text-gray-400">モニター経費</dt>
                <dd class="dark:text-gray-200">¥{{ number_format($purchaseAmt) }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">モニター協力金</dt>
                <dd class="text-pink-600 dark:text-pink-400 font-medium">¥{{ number_format($coopFee) }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">支払合計</dt>
                <dd class="font-bold dark:text-gray-200">¥{{ number_format($purchaseAmt + $coopFee) }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">お支払方法</dt>
                <dd class="dark:text-gray-200">{{ $paymentLabel }}</dd>
                @endif
            </dl>
            @if($report->purchase_type === 'other' && $report->report_body)
            <div class="mt-4 pt-4 border-t dark:border-gray-600">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">報告内容</p>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $report->report_body }}</p>
            </div>
            @endif
        </div>

        {{-- 報告画像 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">報告画像</h2>
            @if($report->images->isNotEmpty())
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($report->images as $image)
                <img src="{{ Storage::url($image->image_path) }}"
                     class="w-full rounded-lg border dark:border-gray-600 cursor-pointer hover:opacity-80 transition"
                     onclick="openLightbox(this.src)">
                @endforeach
            </div>
            @else
            <p class="text-sm text-gray-400">画像なし</p>
            @endif
        </div>

        {{-- 承認・差戻し --}}
        @if($report->status === 'pending')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">審査</h2>
            <div class="flex gap-3 flex-wrap">
                <form method="POST" action="{{ route('admin.reports.approve', $report) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            onclick="return confirm('承認しますか？')"
                            class="bg-green-600 text-white px-5 py-2 rounded hover:bg-green-700 text-sm">
                        ✓ 承認する
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.reports.reject', $report) }}" class="flex-1 min-w-64">
                    @csrf @method('PATCH')
                    <textarea name="reject_reason" rows="2" required placeholder="差戻し理由を入力..."
                              class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm mb-2"></textarea>
                    <button type="submit"
                            class="bg-red-500 text-white px-5 py-2 rounded hover:bg-red-600 text-sm">
                        差戻す
                    </button>
                </form>
            </div>
        </div>
        @elseif($report->status === 'approved')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">協力金付与</h2>
            @if($report->application?->status === 'approved')
            <p class="text-sm text-gray-800 dark:text-gray-400 mb-3">
                付与金額：<span class="font-bold text-pink-600 dark:text-pink-400">¥{{ number_format($report->campaign?->cooperation_fee ?? 0) }}</span>
            </p>
            <form method="POST" action="{{ route('admin.points.grant', $report) }}"
                  onsubmit="return confirm('協力金を付与しますか？')">
                @csrf @method('PATCH')
                <button type="submit" class="bg-pink-600 text-white px-5 py-2 rounded hover:bg-pink-700 text-sm">
                    協力金を付与する
                </button>
            </form>
            @else
            <p class="text-sm text-green-600 dark:text-green-400">✓ 協力金付与済み</p>
            @endif
        </div>
        @elseif($report->status === 'rejected' && $report->reject_reason)
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-sm font-medium text-red-700 dark:text-red-400 mb-1">差戻し理由</p>
            <p class="text-sm text-red-600 dark:text-red-300">{{ $report->reject_reason }}</p>
        </div>
        @endif

    </div>

    {{-- サイドバー --}}
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 text-sm">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">応募情報</h2>
            <dl class="space-y-2">
                <dt class="text-gray-700 dark:text-gray-400">モニター</dt>
                <dd class="font-medium dark:text-gray-200">{{ $report->user?->name ?? '-' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400">案件</dt>
                <dd class="dark:text-gray-200">{{ $report->campaign?->title ?? '-' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400">種別</dt>
                <dd class="dark:text-gray-200">{{ $report->campaign?->getTypeLabel() ?? '-' }}</dd>
                <dt class="text-gray-700 dark:text-gray-400">協力金</dt>
                <dd class="font-medium text-pink-600 dark:text-pink-400">¥{{ number_format($report->campaign?->cooperation_fee ?? 0) }}</dd>
                <dt class="text-gray-700 dark:text-gray-400">報告日</dt>
                <dd class="dark:text-gray-200">{{ $report->created_at->format('Y/m/d H:i') }}</dd>
                @if($report->reviewed_at)
                <dt class="text-gray-700 dark:text-gray-400">審査日</dt>
                <dd class="dark:text-gray-200">{{ $report->reviewed_at->format('Y/m/d H:i') }}</dd>
                @endif
            </dl>
            @if($report->application)
            <div class="mt-3">
                <a href="{{ route('admin.applications.show', $report->application) }}"
                   class="bg-pink-500 text-white px-2 py-1 rounded hover:bg-pink-600 text-xs">→ 応募詳細を見る</a>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ライトボックス --}}
<div id="lightbox" class="fixed inset-0 bg-black bg-opacity-80 z-50 hidden flex items-center justify-center p-4"
     onclick="closeLightbox()">
    <img id="lightbox-img" src="" class="max-w-full max-h-full rounded-lg shadow-xl object-contain">
</div>

@push('scripts')
<script>
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.remove('hidden');
    document.getElementById('lightbox').classList.add('flex');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.add('hidden');
    document.getElementById('lightbox').classList.remove('flex');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});
</script>
@endpush
@endsection
