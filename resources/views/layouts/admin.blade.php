<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIMONI 管理画面 - @yield('title', 'ダッシュボード')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-gray-100 min-h-screen">

    <nav class="bg-pink-600 shadow">
        <div class="px-4 py-3 flex items-center justify-between">
            <span class="font-bold text-lg text-white">BIMONI 管理画面</span>

            <div class="flex items-center gap-1 text-sm">
                <a href="{{ route('admin.dashboard') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    ダッシュボード
                </a>
                <a href="{{ route('admin.campaigns.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    案件管理
                </a>
                <a href="{{ route('admin.daily_slots.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    日別件数管理
                </a>
                <a href="{{ route('admin.approval_reflections.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    承認反映
                </a>
                <a href="{{ route('admin.campaign_bonuses.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    キャンペーン
                </a>
                <a href="{{ route('admin.applications.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    応募管理
                </a>
                <a href="{{ route('admin.reports.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    報告管理
                </a>
                <a href="{{ route('admin.collection_reports.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    回収管理
                </a>
                <a href="{{ route('admin.users.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    ユーザー管理
                </a>
                <a href="{{ route('admin.line_links.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    LINE紐付け
                </a>
                <a href="{{ route('admin.points.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    協力金管理
                </a>
                <a href="{{ route('admin.referrals.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    紹介報酬
                </a>
                <a href="{{ route('admin.agents.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    代理店
                </a>
                <a href="{{ route('admin.import.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    インポート
                </a>
                <a href="{{ route('admin.form_fields.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                    ページ編集
                </a>

                <form method="POST" action="{{ route('admin.logout') }}" class="inline ml-2">
                    @csrf
                    <button type="submit"
                            class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors">
                        ログアウト
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="px-4 py-6 text-gray-800">
        @yield('content')
    </main>

</body>
</html>
