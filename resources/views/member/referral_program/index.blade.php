@extends('layouts.member')

@section('title', 'お友達招待プログラム')

@section('content')
<div class="py-2">

    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('member.mypage') }}" class="text-pink-500 text-sm">← マイページ</a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-5">
        <p class="font-bold text-gray-800 mb-1">🎁 お友達を招待しよう！</p>
        <p class="text-xs text-gray-500 mb-4">招待したお友達が初めてモニター実施を完了すると、あなたに1,000Pがポイント還元されます。</p>

        <p class="text-xs text-gray-500 mb-1">あなたの招待リンク</p>
        <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 mb-3">
            <input type="text" id="referral-link-input" value="{{ $referralLink }}" readonly
                   class="flex-1 min-w-0 bg-transparent text-xs text-gray-600 outline-none">
        </div>
        <button type="button" onclick="copyReferralLink()"
                class="w-full bg-pink-500 text-white py-3 rounded-xl text-sm font-medium">
            リンクをコピー
        </button>
    </div>

    {{-- 実績 --}}
    <p class="font-bold text-gray-700 mb-3">あなたの実績</p>
    <div class="grid grid-cols-2 gap-3 mb-5">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">招待人数</p>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['referral_count']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">初回実施数</p>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['first_use_count']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">今月獲得P</p>
            <p class="text-2xl font-bold text-pink-600">{{ number_format($stats['this_month_points']) }}P</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">累計獲得P</p>
            <p class="text-2xl font-bold text-pink-600">{{ number_format($stats['total_points']) }}P</p>
        </div>
    </div>

    {{-- 獲得履歴 --}}
    <p class="font-bold text-gray-700 mb-3">獲得履歴</p>
    <div class="space-y-2 pb-8">
        @forelse($rewards as $reward)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-400">{{ $reward->created_at->format('n/j') }}</p>
                <p class="text-sm text-gray-800">お友達招待プログラム</p>
            </div>
            <p class="font-bold text-pink-600">＋{{ number_format($reward->amount) }}P</p>
        </div>
        @empty
        <p class="text-sm text-gray-400 text-center py-6">まだ獲得履歴はありません</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyReferralLink() {
    // readonly の既存inputをそのまま選択してコピーする（新規にeditable要素を作るとiOSでキーボードが開いてしまう）
    const input = document.getElementById('referral-link-input');
    try {
        input.focus();
        input.setSelectionRange(0, input.value.length);
        document.execCommand('copy');
    } catch (e) {
        if (navigator.clipboard) navigator.clipboard.writeText(input.value).catch(() => {});
    }
    alert('コピーしました');
}
</script>
@endpush
