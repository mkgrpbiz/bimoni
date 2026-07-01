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

            @php
                $authAdmin = auth('web')->user();
                $navMenus = [
                    'dashboard'             => ['label' => 'ダッシュボード',  'route' => 'admin.dashboard'],
                    'campaigns'             => ['label' => '案件管理',        'route' => 'admin.campaigns.index'],
                    'daily_slots'           => ['label' => '日別件数管理',    'route' => 'admin.daily_slots.index'],
                    'approval_reflections'  => ['label' => '承認反映',        'route' => 'admin.approval_reflections.index'],
                    'campaign_bonuses'      => ['label' => 'キャンペーン',    'route' => 'admin.campaign_bonuses.index'],
                    'applications'          => ['label' => '応募管理',        'route' => 'admin.applications.index'],
                    'proposal_reservations' => ['label' => '状況確認',        'route' => 'admin.proposal_reservations.index'],
                    'reports'               => ['label' => '報告管理',        'route' => 'admin.reports.index'],
                    'collection_reports'    => ['label' => '回収管理',        'route' => 'admin.collection_reports.index'],
                    'users'                 => ['label' => 'ユーザー管理',    'route' => 'admin.users.index'],
                    'line_links'            => ['label' => 'LINE紐付け',      'route' => 'admin.line_links.index'],
                    'points'                => ['label' => '協力金管理',      'route' => 'admin.points.index'],
                    'referrals'             => ['label' => '紹介報酬',        'route' => 'admin.referrals.index'],
                    'agents'                => ['label' => '代理店',          'route' => 'admin.agents.index'],
                    'import'                => ['label' => 'インポート',      'route' => 'admin.import.index'],
                    'form_fields'           => ['label' => 'ページ編集',      'route' => 'admin.form_fields.index'],
                ];
            @endphp
            <div class="flex items-center gap-1 text-sm">
                @foreach($navMenus as $key => $menu)
                    @if($authAdmin?->canAccessMenu($key))
                    <a href="{{ route($menu['route']) }}"
                       class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors whitespace-nowrap">
                        {{ $menu['label'] }}
                    </a>
                    @endif
                @endforeach

                @if($authAdmin?->isAdmin())
                <a href="{{ route('admin.admins.index') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors whitespace-nowrap">
                    管理者
                </a>
                @endif

                <a href="{{ route('admin.profile.edit') }}"
                   class="px-3 py-1.5 rounded text-pink-100 hover:bg-pink-500 transition-colors whitespace-nowrap ml-2">
                    パスワード
                </a>
                <form method="POST" action="{{ route('admin.logout') }}" class="inline">
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

    @stack('scripts')
</body>
</html>
