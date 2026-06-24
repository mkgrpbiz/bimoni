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
