<!DOCTYPE html>
<html lang="ja" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIMONI 管理画面 - @yield('title', 'ダッシュボード')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // ページ読み込み前にダークモードを適用（チカつき防止）
        if (localStorage.getItem('theme') === 'dark' ||
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen transition-colors duration-200">

    <nav class="bg-pink-600 dark:bg-gray-800 shadow">
        <div class="px-4 py-3 flex items-center justify-between">
            <span class="font-bold text-lg text-white">BIMONI 管理画面</span>

            <div class="flex items-center gap-1 text-sm">
                <a href="{{ route('admin.dashboard') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                    ダッシュボード
                </a>
                <a href="{{ route('admin.campaigns.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                    案件管理
                </a>
                <a href="{{ route('admin.applications.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                    応募管理
                </a>
                <a href="{{ route('admin.reports.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                    報告管理
                </a>
                <a href="{{ route('admin.points.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                    ポイント
                </a>
                <a href="{{ route('admin.settlements.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                    月末締め
                </a>
                <a href="{{ route('admin.notifications.line') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                    LINE通知
                </a>
                <a href="{{ route('admin.import.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                    インポート
                </a>
                <a href="{{ route('admin.form_fields.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                    フォーム設定
                </a>

                {{-- ダークモード切り替えボタン --}}
                <button id="theme-toggle"
                        class="ml-2 px-2 py-1.5 rounded text-pink-100 hover:bg-pink-500 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                        title="ライト/ダークモード切り替え">
                    <span id="theme-icon-light" class="hidden">☀️</span>
                    <span id="theme-icon-dark" class="hidden">🌙</span>
                </button>

                <form method="POST" action="{{ route('admin.logout') }}" class="inline ml-2">
                    @csrf
                    <button type="submit"
                            class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                        ログアウト
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="px-4 py-6 text-gray-800 dark:text-gray-200">
        @yield('content')
    </main>

    <script>
        const toggle = document.getElementById('theme-toggle');
        const iconLight = document.getElementById('theme-icon-light');
        const iconDark = document.getElementById('theme-icon-dark');
        const html = document.documentElement;

        function updateIcon() {
            if (html.classList.contains('dark')) {
                iconLight.classList.remove('hidden');
                iconDark.classList.add('hidden');
            } else {
                iconDark.classList.remove('hidden');
                iconLight.classList.add('hidden');
            }
        }

        updateIcon();

        toggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
            updateIcon();
        });
    </script>
</body>
</html>
