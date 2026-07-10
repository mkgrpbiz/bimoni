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

    @php
        $authAdmin = auth('web')->user();
        $navGroups = [
            '案件関連' => [
                'campaigns'             => ['label' => '案件管理',     'route' => 'admin.campaigns.index'],
                'daily_slots'           => ['label' => '日別件数管理', 'route' => 'admin.daily_slots.index'],
                'campaign_bonuses'      => ['label' => 'キャンペーン', 'route' => 'admin.campaign_bonuses.index'],
                'approval_reflections'  => ['label' => '承認反映',     'route' => 'admin.approval_reflections.index'],
            ],
            '応募・打診関連' => [
                'applications'          => ['label' => '応募管理', 'route' => 'admin.applications.index'],
                'proposal_reservations' => ['label' => '状況確認', 'route' => 'admin.proposal_reservations.index'],
                'manual_addition'       => ['label' => '手動追加', 'route' => 'admin.manual_addition.index'],
            ],
            '協力金関連' => [
                'reports'            => ['label' => '報告管理',   'route' => 'admin.reports.index'],
                'collection_reports' => ['label' => '回収管理',   'route' => 'admin.collection_reports.index'],
                'points'             => ['label' => '協力金管理', 'route' => 'admin.points.index'],
            ],
            'ユーザー・代理店関連' => [
                'users'      => ['label' => 'ユーザー管理', 'route' => 'admin.users.index'],
                'line_links' => ['label' => 'LINE紐付け',   'route' => 'admin.line_links.index'],
                'agents'     => ['label' => '代理店',       'route' => 'admin.agents.index'],
                'referrals'  => ['label' => '紹介報酬',     'route' => 'admin.referrals.index'],
            ],
            '設定・その他' => [
                'import'      => ['label' => 'インポート', 'route' => 'admin.import.index'],
                'form_fields' => ['label' => '編集',       'route' => 'admin.form_fields.index'],
            ],
        ];
    @endphp

    {{-- 狭い画面用オーバーレイ --}}
    <div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/40 z-20 lg:hidden"></div>

    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-30 w-64 bg-pink-700 text-pink-100 overflow-y-auto
                  flex flex-col transform -translate-x-full transition-transform duration-200">
        <div class="px-4 py-4 font-bold text-lg text-white border-b border-pink-600 flex items-center justify-between shrink-0">
            <span>BIMONI 管理画面</span>
            <button id="sidebar-close" type="button" class="text-pink-200 hover:text-white text-2xl leading-none px-1">
                ×
            </button>
        </div>

        <nav class="py-2 text-sm flex-1">
            @if($authAdmin?->canAccessMenu('dashboard'))
            <a href="{{ route('admin.dashboard') }}"
               class="block px-4 py-2.5 hover:bg-pink-600 transition-colors font-medium">
                ダッシュボード
            </a>
            @endif

            @foreach($navGroups as $groupLabel => $items)
                @php
                    $visibleItems = collect($items)->filter(fn($m, $key) => $authAdmin?->canAccessMenu($key));
                @endphp
                @if($visibleItems->isNotEmpty())
                <div class="border-t border-pink-600">
                    <button type="button" class="nav-group-header w-full flex items-center justify-between px-4 py-2.5 text-left hover:bg-pink-600 transition-colors font-medium">
                        <span>{{ $groupLabel }}</span>
                        <span class="nav-group-icon text-pink-200 text-xs select-none">▶</span>
                    </button>
                    <div class="nav-group-body hidden bg-pink-800/40">
                        @foreach($visibleItems as $key => $menu)
                        <a href="{{ route($menu['route']) }}"
                           class="block pl-7 pr-4 py-2 hover:bg-pink-600 transition-colors">
                            {{ $menu['label'] }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach

            @if($authAdmin?->isAdmin())
            <div class="border-t border-pink-600">
                <a href="{{ route('admin.admins.index') }}"
                   class="block px-4 py-2.5 hover:bg-pink-600 transition-colors font-medium">
                    管理者
                </a>
            </div>
            @endif
        </nav>

        <div class="border-t border-pink-600 shrink-0">
            <a href="{{ route('admin.profile.edit') }}"
               class="block px-4 py-2.5 hover:bg-pink-600 transition-colors">
                パスワード
            </a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit"
                        class="block w-full text-left px-4 py-2.5 hover:bg-pink-600 transition-colors">
                    ログアウト
                </button>
            </form>
        </div>
    </aside>

    <div id="content-wrapper" class="transition-[padding-left] duration-200">
        <div class="bg-pink-600 shadow flex items-center gap-3 px-4 py-3">
            <button id="sidebar-toggle" type="button" class="text-white p-1 -ml-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <span class="font-bold text-white">BIMONI 管理画面</span>
        </div>

        <main class="px-4 py-6 text-gray-800">
            @yield('content')
        </main>
    </div>

    <script>
    (function () {
        var sidebar  = document.getElementById('sidebar');
        var overlay  = document.getElementById('sidebar-overlay');
        var toggle   = document.getElementById('sidebar-toggle');
        var closeBtn = document.getElementById('sidebar-close');
        var wrapper  = document.getElementById('content-wrapper');

        function isDesktop() {
            return window.matchMedia('(min-width: 1024px)').matches;
        }

        function setSidebarOpen(open) {
            sidebar.classList.toggle('-translate-x-full', !open);
            wrapper.classList.toggle('sidebar-open', open);
            overlay.classList.toggle('hidden', !(open && !isDesktop()));
        }

        toggle.addEventListener('click', function () {
            setSidebarOpen(sidebar.classList.contains('-translate-x-full'));
        });
        closeBtn.addEventListener('click', function () { setSidebarOpen(false); });
        overlay.addEventListener('click', function () { setSidebarOpen(false); });

        setSidebarOpen(isDesktop());

        document.querySelectorAll('.nav-group-header').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var body = btn.parentElement.querySelector('.nav-group-body');
                var icon = btn.querySelector('.nav-group-icon');
                var isOpen = !body.classList.contains('hidden');
                body.classList.toggle('hidden', isOpen);
                icon.textContent = isOpen ? '▶' : '▼';
            });
        });
    })();
    </script>

    @stack('scripts')
</body>
</html>
