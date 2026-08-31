<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIMONI 案件一覧 - @yield('title', '案件一覧')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-pink-700 text-white px-4 py-3 flex items-center justify-between shadow">
        <span class="font-bold">BIMONI 案件一覧</span>
        @auth('ad_agency_share')
        <form method="POST" action="{{ route('ad_agency_share.logout') }}">
            @csrf
            <button type="submit" class="text-pink-100 text-sm hover:text-white">ログアウト</button>
        </form>
        @endauth
    </header>

    <main class="p-4 max-w-6xl mx-auto">
        @yield('content')
    </main>
</body>
</html>
