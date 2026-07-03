@extends('layouts.admin')
@section('title', '回収報告詳細')
@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.collection_reports.index') }}"
       class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">← 回収管理</a>
    <h1 class="text-2xl font-bold text-gray-800">回収報告詳細</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- 報告内容 --}}
    <div class="lg:col-span-2 space-y-4">

        <div class="bg-white rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 mb-4">報告内容</h2>

            {{-- 対象案件 --}}
            <div class="mb-4">
                <p class="text-xs text-gray-500 mb-1">対象案件（{{ $campaigns->count() }}商品）</p>
                <div class="space-y-1">
                    @foreach($campaigns as $c)
                    <div class="text-sm bg-gray-50 rounded px-3 py-1.5">{{ $c->title }}</div>
                    @endforeach
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-y-3 text-sm">
                <dt class="text-gray-500">協力金</dt>
                <dd class="font-bold text-pink-600 text-base">¥{{ number_format($collectionReport->cooperation_fee) }}
                    <span class="text-xs font-normal text-gray-400 ml-1">
                        ({{ $collectionReport->item_count }}点×800円
                        @if($collectionReport->item_count < 5) - 送料¥{{ number_format($collectionReport->shipping_fee) }}@endif)
                    </span>
                </dd>
                <dt class="text-gray-500">到着予定日</dt>
                <dd>{{ $collectionReport->estimated_arrival_date?->format('Y/m/d') ?? '-' }}</dd>
                <dt class="text-gray-500">追跡番号</dt>
                <dd class="font-mono">{{ $collectionReport->tracking_number }}</dd>
                <dt class="text-gray-500">送料</dt>
                <dd>¥{{ number_format($collectionReport->shipping_fee) }}</dd>
                <dt class="text-gray-500">報告日時</dt>
                <dd>{{ $collectionReport->created_at->format('Y/m/d H:i') }}</dd>
                <dt class="text-gray-500">ステータス</dt>
                <dd>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $collectionReport->getStatusColor() }}">
                        {{ $collectionReport->getStatusLabel() }}
                    </span>
                </dd>
            </dl>
        </div>

        {{-- 添付画像 --}}
        <div class="bg-white rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 mb-4">添付画像</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 mb-2">段ボール（閉じる前）</p>
                    @if($collectionReport->box_image)
                    <img src="{{ asset('storage/' . $collectionReport->box_image) }}"
                         class="w-full rounded-lg border cursor-pointer hover:opacity-80 transition"
                         onclick="openLightbox(this.src)">
                    @else
                    <div class="w-full rounded-lg border bg-gray-50 flex items-center justify-center h-32 text-gray-400 text-sm">画像なし</div>
                    @endif
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-2">発送伝票の控え</p>
                    @if($collectionReport->label_image)
                    <img src="{{ asset('storage/' . $collectionReport->label_image) }}"
                         class="w-full rounded-lg border cursor-pointer hover:opacity-80 transition"
                         onclick="openLightbox(this.src)">
                    @else
                    <div class="w-full rounded-lg border bg-gray-50 flex items-center justify-center h-32 text-gray-400 text-sm">画像なし</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 差戻し理由（差戻し済みの場合） --}}
        @if($collectionReport->status === 'rejected' && $collectionReport->rejection_reason)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-sm font-bold text-red-700 mb-1">差戻し理由</p>
            <p class="text-sm text-red-600">{{ $collectionReport->rejection_reason }}</p>
        </div>
        @endif

        {{-- 承認・差戻しアクション --}}
        @if($collectionReport->status === 'pending')
        <div class="bg-white rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 mb-4">審査アクション</h2>
            <div class="flex gap-3 mb-4">
                <form method="POST" action="{{ route('admin.collection_reports.approve', $collectionReport) }}">
                    @csrf @method('PATCH')
                    <button type="submit" onclick="return confirm('承認しますか？')"
                            class="bg-green-500 text-white px-5 py-2 rounded hover:bg-green-600 text-sm font-bold">
                        承認
                    </button>
                </form>
                <button type="button" onclick="document.getElementById('reject-form').classList.toggle('hidden')"
                        class="bg-red-500 text-white px-5 py-2 rounded hover:bg-red-600 text-sm font-bold">
                    差戻し
                </button>
            </div>
            <form id="reject-form" method="POST"
                  action="{{ route('admin.collection_reports.reject', $collectionReport) }}"
                  class="hidden border-t pt-4">
                @csrf @method('PATCH')
                @error('rejection_reason')
                    <p class="text-red-500 text-xs mb-2">{{ $message }}</p>
                @enderror
                <label class="block text-sm font-medium text-gray-700 mb-1">差戻し理由</label>
                <textarea name="rejection_reason" rows="3" required
                          class="w-full border rounded px-3 py-2 text-sm mb-3"
                          placeholder="差戻しの理由を入力してください">{{ old('rejection_reason') }}</textarea>
                <button type="submit"
                        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm">
                    差戻しを確定
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- ユーザー情報 --}}
    <div class="space-y-4">
        <div class="bg-white rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 mb-3">ユーザー情報</h2>
            @php $u = $collectionReport->user; @endphp
            <dl class="text-sm space-y-2">
                <dt class="text-gray-500">ユーザーID</dt>
                <dd class="font-mono text-xs">{{ $u->bimoni_user_id ?? '-' }}</dd>
                <dt class="text-gray-500">LINE表示名</dt>
                <dd>{{ $u->line_display_name ?? '-' }}</dd>
                <dt class="text-gray-500">名前</dt>
                <dd class="font-medium">{{ $u->name ?? '-' }}</dd>
                <dt class="text-gray-500">フリガナ</dt>
                <dd>{{ $u->name_kana ?? '-' }}</dd>
                <dt class="text-gray-500">エリア</dt>
                <dd>{{ $u->area ?? '-' }}</dd>
            </dl>
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
