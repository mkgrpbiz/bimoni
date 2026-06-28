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
    <div id="line-friend-modal" class="fixed inset-0 bg-pink-900 bg-opacity-30 z-50 flex items-center justify-center">
        <div class="bg-white rounded-3xl mx-4 max-w-sm w-full text-center shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-br from-pink-400 to-pink-500 px-6 pt-8 pb-7">
                <img src="{{ asset('images/bimoni-logo.png') }}" alt="BIMONI" class="w-20 h-20 mx-auto mb-3">
                <h2 class="text-white font-bold text-lg tracking-wide">BIMONI【公式】</h2>
            </div>
            <div class="px-6 py-7">
                <p class="text-gray-700 font-medium text-sm mb-2">LINEの追加お願いします</p>
                <p class="text-gray-400 text-xs mb-7">※案内は全てLINEから送信されます</p>
                <a href="https://line.me/R/ti/p/@204zmull"
                   class="block w-full bg-green-500 text-white py-4 rounded-2xl font-bold text-sm shadow-md">
                    LINE追加する
                </a>
            </div>
        </div>
    </div>
    <script>
    (function() {
        if (typeof liff === 'undefined') return;
        liff.init({ liffId: '{{ config("services.line.liff_id") }}' })
            .then(() => {
                if (!liff.isLoggedIn()) return null;
                return liff.getFriendship();
            })
            .then(friendship => {
                console.log('friendship:', JSON.stringify(friendship));
                if (friendship && !friendship.friendFlag) {
                    const modal = document.getElementById('line-friend-modal');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            })
            .catch((err) => {
            console.error('LIFF friendship check error:', err);
        });
    })();
    </script>
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
