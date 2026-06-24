<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIMONI パートナーポータル - @yield('title', 'ダッシュボード')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen pb-20 md:pb-0">

{{-- PC用トップナビ --}}
<nav class="hidden md:block bg-gray-800 shadow">
    <div class="px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="font-bold text-white text-sm">BIMONI</span>
            <span class="text-gray-400 text-xs">{{ $portalAgent->name }}</span>
            @if($portalAgent->parent_id)
                <span class="bg-blue-500 text-white text-xs px-2 py-0.5 rounded">子代理店</span>
            @else
                <span class="bg-pink-500 text-white text-xs px-2 py-0.5 rounded">親代理店</span>
            @endif
        </div>
        <div class="flex items-center gap-1 text-sm">
            <a href="{{ route('portal.users') }}"
               class="px-3 py-1.5 rounded text-gray-300 hover:bg-gray-700 transition-colors {{ request()->routeIs('portal.users') ? 'bg-gray-700' : '' }}">
                ユーザー
            </a>
            <a href="{{ route('portal.reports') }}"
               class="px-3 py-1.5 rounded text-gray-300 hover:bg-gray-700 transition-colors {{ request()->routeIs('portal.reports') ? 'bg-gray-700' : '' }}">
                報告
            </a>
            <a href="{{ route('portal.rewards') }}"
               class="px-3 py-1.5 rounded text-gray-300 hover:bg-gray-700 transition-colors {{ request()->routeIs('portal.rewards*') ? 'bg-gray-700' : '' }}">
                報酬
            </a>
            @if(!$portalAgent->parent_id)
            <a href="{{ route('portal.children') }}"
               class="px-3 py-1.5 rounded text-gray-300 hover:bg-gray-700 transition-colors {{ request()->routeIs('portal.children*') ? 'bg-gray-700' : '' }}">
                子管理
            </a>
            @endif
            <a href="{{ route('portal.settings') }}"
               class="px-3 py-1.5 rounded text-gray-300 hover:bg-gray-700 transition-colors {{ request()->routeIs('portal.settings*') ? 'bg-gray-700' : '' }}">
                設定
            </a>
        </div>
    </div>
</nav>

{{-- スマホ用ヘッダー --}}
<header class="md:hidden bg-gray-800 px-4 py-3 flex items-center gap-2">
    <span class="font-bold text-white text-sm">BIMONI</span>
    <span class="text-gray-400 text-xs flex-1 truncate">{{ $portalAgent->name }}</span>
    @if($portalAgent->parent_id)
        <span class="bg-blue-500 text-white text-xs px-2 py-0.5 rounded shrink-0">子</span>
    @else
        <span class="bg-pink-500 text-white text-xs px-2 py-0.5 rounded shrink-0">親</span>
    @endif
</header>

<main class="px-4 py-5 max-w-5xl mx-auto">
    @yield('content')
</main>

{{-- スマホ用ボトムナビ --}}
<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 flex z-50">
    <a href="{{ route('portal.users') }}"
       class="flex-1 flex flex-col items-center gap-0.5 py-2 text-xs {{ request()->routeIs('portal.users') ? 'text-pink-500' : 'text-gray-500' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        ユーザー
    </a>
    <a href="{{ route('portal.reports') }}"
       class="flex-1 flex flex-col items-center gap-0.5 py-2 text-xs {{ request()->routeIs('portal.reports') ? 'text-pink-500' : 'text-gray-500' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        報告
    </a>
    <a href="{{ route('portal.rewards') }}"
       class="flex-1 flex flex-col items-center gap-0.5 py-2 text-xs {{ request()->routeIs('portal.rewards*') ? 'text-pink-500' : 'text-gray-500' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        報酬
    </a>
    @if(!$portalAgent->parent_id)
    <a href="{{ route('portal.children') }}"
       class="flex-1 flex flex-col items-center gap-0.5 py-2 text-xs {{ request()->routeIs('portal.children*') ? 'text-pink-500' : 'text-gray-500' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        子管理
    </a>
    @endif
    <a href="{{ route('portal.settings') }}"
       class="flex-1 flex flex-col items-center gap-0.5 py-2 text-xs {{ request()->routeIs('portal.settings*') ? 'text-pink-500' : 'text-gray-500' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        設定
    </a>
</nav>

</body>
</html>
