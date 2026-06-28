<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BIMONI - @yield('title', 'ビモニ')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(config('services.line.liff_id'))
    <script charset="utf-8" src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    @endif
</head>
<body class="bg-gray-50 min-h-screen pb-20">

    <header class="bg-pink-500 text-white shadow-md sticky top-0 z-50">
        <div class="flex items-center justify-between px-4 py-3 max-w-lg mx-auto">
            <a href="{{ route('member.campaigns.index') }}" class="font-bold text-lg tracking-wide">
                BIMONI
            </a>
            @auth('liff')
            <nav class="flex gap-4 text-sm">
                <a href="{{ route('member.campaigns.index') }}" class="opacity-90 hover:opacity-100">案件一覧</a>
                <a href="{{ route('member.mypage') }}" class="opacity-90 hover:opacity-100">マイページ</a>
            </nav>
            @endauth
        </div>
    </header>

    <main class="max-w-lg mx-auto px-4 py-5">
        @if(session('success'))
            <div class="bg-green-50 border border-green-300 text-green-700 rounded-lg px-4 py-3 mb-4 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-300 text-red-600 rounded-lg px-4 py-3 mb-4 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')

    @auth('liff')
    @if(!request()->routeIs('member.register*'))
    <style>
    @keyframes bimoni-popup {
        from { opacity: 0; transform: scale(0.8) translateY(20px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    #line-friend-modal-card {
        animation: bimoni-popup 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }
    </style>
    <div id="line-friend-modal" class="fixed inset-0 z-50 hidden items-center justify-center" style="background: rgba(0,0,0,0.25); backdrop-filter: blur(4px);">
        <div id="line-friend-modal-card" class="bg-white rounded-3xl mx-4 w-full max-w-xs text-center overflow-hidden" style="box-shadow: 0 20px 60px rgba(0,0,0,0.18);">
            <div class="bg-gradient-to-br from-pink-400 to-pink-500 px-4 pt-4 pb-4">
                <img src="{{ asset('images/bimoni-logo.png') }}" alt="BIMONI" class="w-16 h-16 mx-auto mb-2 drop-shadow-md">
                <h2 class="text-gray-800 font-bold text-base tracking-wide leading-tight">BIMONI【公式】</h2>
            </div>
            <div class="px-5 pt-4 pb-6">
                <p class="text-gray-700 font-semibold text-sm mb-1">LINEの追加お願いします</p>
                <p class="text-gray-400 text-xs mb-3">※案内は全てLINEから送信されます</p>
                <a href="https://line.me/R/ti/p/@204zmull"
                   class="flex items-center justify-center gap-2 w-full bg-green-500 text-white py-3 rounded-2xl font-bold text-sm shadow-md active:scale-95 transition-transform">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                    </svg>
                    LINE追加する
                </a>
            </div>
        </div>
    </div>
    <script>
    (function() {
        function showModal() {
            const modal = document.getElementById('line-friend-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        if (typeof liff === 'undefined') { showModal(); return; }
        liff.init({ liffId: '{{ config("services.line.liff_id") }}' })
            .then(() => {
                if (!liff.isLoggedIn()) { showModal(); return; }
                return liff.getFriendship();
            })
            .then(friendship => {
                if (!friendship || !friendship.friendFlag) {
                    showModal();
                }
            })
            .catch(() => { showModal(); });
    })();
    </script>
    @endif
    @endauth

    @auth('liff')
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50">
        <div class="flex max-w-lg mx-auto">
            <a href="{{ route('member.campaigns.index') }}"
               class="flex-1 flex flex-col items-center py-2.5 text-xs {{ request()->routeIs('member.campaigns*') ? 'text-pink-500' : 'text-gray-500' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                案件一覧
            </a>
            <a href="{{ route('member.mypage') }}"
               class="flex-1 flex flex-col items-center py-2.5 text-xs {{ request()->routeIs('member.mypage') ? 'text-pink-500' : 'text-gray-500' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                マイページ
            </a>
        </div>
    </nav>
    @endauth

</body>
</html>
