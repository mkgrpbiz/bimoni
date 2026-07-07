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
                $adjustAmt = $report->adjustment_amount ?? 0;
            @endphp
            <dl class="grid grid-cols-2 gap-y-3 text-sm">
                <dt class="text-gray-500 dark:text-gray-400">BIMONI ID</dt>
                <dd class="font-medium dark:text-gray-200">{{ $report->user?->bimoni_user_id ?? '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">LINE表示名</dt>
                <dd class="dark:text-gray-200">{{ $report->user?->line_display_name ?? '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">名前</dt>
                <dd class="dark:text-gray-200">{{ $report->user?->name ?? '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">フリガナ</dt>
                <dd class="dark:text-gray-200">{{ $report->user?->name_kana ?? '-' }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">報告種別</dt>
                <dd class="font-medium dark:text-gray-200">{{ $purchaseTypeLabel }}</dd>
                <dt class="text-gray-500 dark:text-gray-400">モニター経費</dt>
                <dd class="dark:text-gray-200">¥{{ number_format($purchaseAmt) }}</dd>
                @if($report->purchase_type !== 'other')
                <dt class="text-gray-500 dark:text-gray-400">モニター協力金</dt>
                <dd class="text-pink-600 dark:text-pink-400 font-medium">¥{{ number_format($coopFee) }}</dd>
                @endif
                @if($adjustAmt)
                <dt class="text-gray-500 dark:text-gray-400">修正金額</dt>
                <dd class="dark:text-gray-200 {{ $adjustAmt > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $adjustAmt > 0 ? '+' : '' }}¥{{ number_format($adjustAmt) }}</dd>
                @endif
                @if($report->purchase_type !== 'other')
                <dt class="text-gray-500 dark:text-gray-400">支払合計</dt>
                <dd class="font-bold dark:text-gray-200">¥{{ number_format($purchaseAmt + $coopFee + $adjustAmt) }}</dd>
                @endif
                @if($paymentLabel !== '-')
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

        {{-- 金額修正 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">金額修正</h2>
            @if($report->adjustment_amount)
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                現在の修正: <span class="font-bold text-pink-600">{{ $report->adjustment_amount > 0 ? '+' : '' }}¥{{ number_format($report->adjustment_amount) }}</span>
                <span class="text-gray-400 ml-2">（{{ $report->adjustment_reason }}）</span>
            </p>
            @endif
            <form method="POST" action="{{ route('admin.reports.adjust', $report) }}" class="space-y-3">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">修正理由</label>
                    <input type="text" name="adjustment_reason" required maxlength="255"
                           value="{{ old('adjustment_reason', $report->adjustment_reason) }}"
                           placeholder="例: 送料補填、特別ボーナス"
                           class="w-full border rounded px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">修正金額（±）</label>
                    <input type="number" name="adjustment_amount" required
                           value="{{ old('adjustment_amount', $report->adjustment_amount) }}"
                           placeholder="例: 500 または -500"
                           class="w-40 border rounded px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                </div>
                <button type="submit"
                        onclick="return confirm('金額を修正しますか？')"
                        class="bg-gray-700 text-white px-5 py-2 rounded hover:bg-gray-800 text-sm">
                    修正する
                </button>
            </form>
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
            <div class="flex gap-3 flex-wrap mb-3">
                <form method="POST" action="{{ route('admin.reports.approve', $report) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            onclick="return confirm('承認しますか？')"
                            class="bg-green-600 text-white px-5 py-2 rounded hover:bg-green-700 text-sm">
                        ✓ 承認する
                    </button>
                </form>
                <button type="button" onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                        class="bg-red-500 text-white px-5 py-2 rounded hover:bg-red-600 text-sm">
                    差戻す
                </button>
            </div>
            <form id="reject-form" method="POST" action="{{ route('admin.reports.reject', $report) }}"
                  class="hidden border-t pt-4">
                @csrf @method('PATCH')
                @error('reject_reason')
                    <p class="text-red-500 text-xs mb-2">{{ $message }}</p>
                @enderror
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">差戻し理由</label>
                <textarea name="reject_reason" rows="3" required
                          class="w-full border rounded px-3 py-2 text-sm mb-3 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                          placeholder="差戻しの理由を入力してください">{{ old('reject_reason') }}</textarea>
                <button type="submit"
                        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm">
                    差戻しを確定
                </button>
            </form>
        </div>
        @elseif($report->status === 'rejected' && $report->reject_reason)
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-sm font-medium text-red-700 dark:text-red-400 mb-1">差戻し理由</p>
            <p class="text-sm text-red-600 dark:text-red-300">{{ $report->reject_reason }}</p>
        </div>
        @endif

        {{-- 重複申請チェック --}}
        @php $dupCount = $duplicates->count(); @endphp
        <div class="{{ $dupCount > 0 ? 'border-2 border-orange-300 bg-orange-50 dark:bg-orange-900/20' : 'border border-gray-200 bg-gray-50 dark:bg-gray-800' }} rounded-lg p-5">
            <h2 class="font-bold {{ $dupCount > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }} mb-4">
                {{ $dupCount > 0 ? '⚠ 重複申請チェック（同一ユーザー・同一案件の過去申請 ' . $dupCount . '件）' : '✓ 重複申請なし' }}
            </h2>
            @if($dupCount > 0)
            <div class="space-y-4">
                @foreach($duplicates as $dup)
                @php
                    $dupPurchaseLabel = match($dup->purchase_type) {
                        'initial'      => '初回購入',
                        'continuation' => '継続購入',
                        'other'        => 'その他',
                        default        => $dup->purchase_type ?? '-',
                    };
                    $dupPayLabel = match(true) {
                        str_starts_with($dup->payment_method ?? '', 'other:') => 'その他: ' . substr($dup->payment_method, 6),
                        $dup->payment_method === 'credit_card' => 'クレジットカード',
                        $dup->payment_method === 'cod'         => '代引き',
                        $dup->payment_method === 'deferred'    => '後払い',
                        $dup->payment_method === 'bank'        => '銀行振込',
                        $dup->payment_method === 'none'        => 'お支払い無し',
                        default => $dup->payment_method ?? '-',
                    };
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-orange-200 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xs text-gray-500">{{ $dup->created_at->format('Y/m/d H:i') }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $dup->getStatusColor() }}">{{ $dup->getStatusLabel() }}</span>
                        <a href="{{ route('admin.reports.show', $dup) }}" class="ml-auto text-xs text-blue-500 hover:underline">→ 詳細</a>
                    </div>
                    <dl class="grid grid-cols-2 gap-y-2 text-sm">
                        <dt class="text-gray-500">報告種別</dt><dd>{{ $dupPurchaseLabel }}</dd>
                        @if($dup->purchase_type !== 'other')
                        <dt class="text-gray-500">モニター経費</dt><dd>¥{{ number_format($dup->purchase_amount ?? 0) }}</dd>
                        <dt class="text-gray-500">協力金</dt><dd class="text-pink-600 font-medium">¥{{ number_format($dup->campaign?->cooperation_fee ?? 0) }}</dd>
                        <dt class="text-gray-500">支払方法</dt><dd>{{ $dupPayLabel }}</dd>
                        @endif
                    </dl>
                    @if($dup->images->isNotEmpty())
                    <div class="flex gap-2 mt-3 flex-wrap">
                        @foreach($dup->images as $img)
                        <img src="{{ Storage::url($img->image_path) }}"
                             class="h-20 w-20 object-cover rounded border cursor-pointer hover:opacity-80"
                             onclick="openLightbox(this.src)">
                        @endforeach
                    </div>
                    @endif
                    @if($dup->purchase_type === 'other' && $dup->report_body)
                    <p class="text-sm text-gray-600 mt-2 whitespace-pre-wrap">{{ $dup->report_body }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>

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
