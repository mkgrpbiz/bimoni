<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>BIMONI モニター募集</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-b from-pink-50 to-white min-h-screen">

<div class="max-w-sm mx-auto px-5 pt-10 pb-16">

    {{-- ロゴ --}}
    <div class="text-center mb-8">
        <img src="{{ asset('images/bimoni-logo.png') }}" alt="BIMONI" class="w-24 h-24 mx-auto mb-2">
        <p class="text-xs text-gray-400">招待制モニター</p>
    </div>

    {{-- エージェント紹介バッジ --}}
    @if($agentName)
    <div class="flex items-center justify-center gap-2 mb-6">
        <span class="h-px flex-1 bg-gray-200"></span>
        <span class="text-xs text-gray-400 whitespace-nowrap">{{ $agentName }} からのご招待</span>
        <span class="h-px flex-1 bg-gray-200"></span>
    </div>
    @endif

    {{-- メインコピー --}}
    <div class="text-center mb-8">
        <p class="text-2xl font-black text-gray-800 leading-tight mb-2">
            美容商品やサービスを<br>
            <span class="text-pink-500">モニターして</span><br>
            協力金をもらおう
        </p>
        <p class="text-sm text-gray-500 leading-relaxed">
            実際に購入や体験し、報告するだけ。<br>
            モニター協力金をお支払いします。
        </p>
    </div>

    {{-- 特徴 --}}
    <div class="space-y-3 mb-8">
        <div class="flex items-start gap-3 bg-white rounded-xl px-4 py-3 shadow-sm border border-gray-100">
            <span class="text-xl shrink-0">💄</span>
            <div>
                <p class="text-sm font-bold text-gray-800">美容商品のモニター</p>
                <p class="text-xs text-gray-500">スキンケア・コスメ・サプリなど多数</p>
            </div>
        </div>
        <div class="flex items-start gap-3 bg-white rounded-xl px-4 py-3 shadow-sm border border-gray-100">
            <span class="text-xl shrink-0">💻</span>
            <div>
                <p class="text-sm font-bold text-gray-800">サービスの体験モニター</p>
                <p class="text-xs text-gray-500">資料請求・口座開設・面談など</p>
            </div>
        </div>
        <div class="flex items-start gap-3 bg-white rounded-xl px-4 py-3 shadow-sm border border-gray-100">
            <span class="text-xl shrink-0">💰</span>
            <div>
                <p class="text-sm font-bold text-gray-800">購入費＋協力金をお支払い</p>
                <p class="text-xs text-gray-500">商品代金は全額還元。さらに謝礼あり</p>
            </div>
        </div>
        <div class="flex items-start gap-3 bg-white rounded-xl px-4 py-3 shadow-sm border border-gray-100">
            <span class="text-xl shrink-0">📱</span>
            <div>
                <p class="text-sm font-bold text-gray-800">LINEで簡単管理</p>
                <p class="text-xs text-gray-500">案件・報告・協力金の確認がすべてLINEで</p>
            </div>
        </div>
    </div>

    {{-- ステップ説明 --}}
    <div class="bg-pink-50 rounded-2xl p-5 mb-8">
        <p class="text-xs font-bold text-pink-600 mb-3 text-center">はじめ方</p>
        <div class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-6 h-6 rounded-full bg-pink-500 text-white text-xs font-bold flex items-center justify-center shrink-0">1</div>
                <p class="text-sm text-gray-700">「LINEで登録する」から登録</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-6 h-6 rounded-full bg-pink-500 text-white text-xs font-bold flex items-center justify-center shrink-0">2</div>
                <p class="text-sm text-gray-700">案件一覧からモニター応募</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-6 h-6 rounded-full bg-pink-500 text-white text-xs font-bold flex items-center justify-center shrink-0">3</div>
                <p class="text-sm text-gray-700">案内がLINEに届きモニター開始</p>
            </div>
        </div>
    </div>

    {{-- CTAボタン --}}
    <div class="space-y-3">
        {{-- メイン: LINEで登録（コード付きLIFF） --}}
        <a href="{{ $liffUrl }}"
           class="block w-full bg-green-500 hover:bg-green-600 text-white font-bold text-base py-4 rounded-2xl text-center shadow-lg transition-all active:scale-95">
            <span class="flex items-center justify-center gap-2">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                </svg>
                LINEで登録する
            </span>
        </a>

    </div>

    {{-- 招待コード表示 --}}
    <div class="mt-8 text-center">
        <p class="text-xs text-gray-300">招待コード: {{ $code }}</p>
    </div>

</div>

<style>
@keyframes invite-popup {
    from { opacity: 0; transform: scale(0.8) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
#line-browser-modal-card {
    animation: invite-popup 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
</style>

<div id="line-browser-modal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background: rgba(0,0,0,0.25); backdrop-filter: blur(4px);">
    <div id="line-browser-modal-card" class="bg-white rounded-3xl mx-4 w-full max-w-xs text-center overflow-hidden" style="box-shadow: 0 20px 60px rgba(0,0,0,0.18);">
        <div class="bg-gradient-to-br from-pink-400 to-pink-500 px-4 pt-4 pb-4">
            <img src="{{ asset('images/bimoni-logo.png') }}" alt="BIMONI" class="w-16 h-16 mx-auto mb-2 drop-shadow-md">
            <h2 class="text-gray-800 font-bold text-base tracking-wide leading-tight">BIMONI【公式】</h2>
        </div>
        <div class="px-5 pt-4 pb-6">
            <p class="text-gray-700 font-semibold text-sm mb-1">LINEウェブアプリの為</p>
            <p class="text-gray-700 text-sm mb-4">LINEブラウザで開く必要があります。</p>
            <a id="line-browser-btn" href="#"
               class="flex items-center justify-center gap-2 w-full bg-green-500 text-white py-3 rounded-2xl font-bold text-sm shadow-md active:scale-95 transition-transform">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                </svg>
                LINEブラウザで開く
            </a>
        </div>
    </div>
</div>

<script>
(function() {
    var isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    var isLine   = /Line\//i.test(navigator.userAgent);
    if (isMobile && !isLine) {
        var modal = document.getElementById('line-browser-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        var lineUrl = 'line://nv/inAppBrowser?url=' + encodeURIComponent(window.location.href);
        document.getElementById('line-browser-btn').href = lineUrl;
    }
})();
</script>

</body>
</html>
