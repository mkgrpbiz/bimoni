@extends('layouts.member')

@section('title', 'マイページ')

@section('content')
<div class="py-2">

    {{-- プロフィール & 支払い予定 --}}
    <div class="bg-gradient-to-r from-pink-500 to-pink-400 text-white rounded-2xl p-5 mb-5 shadow-md">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 bg-white/30 rounded-full flex items-center justify-center text-xl">
                👤
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-lg">{{ $user->name ?? '未設定' }}</p>
                <p class="text-pink-100 text-xs">{{ $user->name_kana ?? '' }}</p>
            </div>
            <a href="{{ route('member.profile.edit') }}"
               class="flex-shrink-0 bg-white/20 text-white text-xs px-3 py-1.5 rounded-full">
                ✏️ 情報変更
            </a>
        </div>

        <p class="text-pink-100 text-xs mb-2 text-center">モニター協力金 支払い予定</p>
        <div class="grid grid-cols-2 gap-2">
            <div class="bg-white/20 rounded-xl px-3 py-3 text-center">
                <p class="text-pink-100 text-xs mb-1">{{ $payCurrentDate }}支払い</p>
                <p class="text-2xl font-bold">¥{{ number_format($payCurrentMonth) }}</p>
            </div>
            <div class="bg-white/20 rounded-xl px-3 py-3 text-center">
                <p class="text-pink-100 text-xs mb-1">{{ $payNextDate }}支払い</p>
                <p class="text-2xl font-bold">¥{{ number_format($payNextMonth) }}</p>
            </div>
        </div>
    </div>

    {{-- アクションボタン --}}
    <div class="space-y-3 mb-5">
        <a href="{{ route('member.reports.create') }}"
           class="block bg-pink-500 text-white py-3 rounded-xl text-sm font-medium text-center">
            📋 モニター報告
        </a>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('member.collections.create') }}"
               class="bg-white border border-gray-200 text-gray-700 py-3 rounded-xl text-sm font-medium text-center">
                📦 回収依頼
            </a>
            <a href="{{ route('member.cancellations') }}"
               class="bg-white border border-gray-200 text-gray-700 py-3 rounded-xl text-sm font-medium text-center">
                📄 解約方法一覧
            </a>
        </div>
    </div>

    {{-- モニター履歴（タブ） --}}
    <h2 class="font-bold text-gray-700 mb-3">モニター履歴</h2>

    @php
        $tabKeys = array_keys($groups);
        $allTabs = array_merge($tabKeys, ['報告済', '回収']);
    @endphp

    <div x-data="{ tab: '{{ $tabKeys[0] }}' }">

        {{-- タブ --}}
        <div class="flex border-b border-gray-200 mb-4 overflow-x-auto">
            @foreach($groups as $label => $apps)
            <button
                @click="tab = '{{ $label }}'"
                :class="tab === '{{ $label }}'
                    ? 'border-b-2 border-pink-500 text-pink-600 font-semibold'
                    : 'text-gray-500'"
                class="flex-1 min-w-0 py-2.5 px-1 text-xs whitespace-nowrap flex flex-col items-center gap-0.5 transition-colors">
                <span>{{ $label }}</span>
                <span
                    :class="tab === '{{ $label }}' ? 'bg-pink-500 text-white' : 'bg-gray-200 text-gray-600'"
                    class="text-xs font-bold px-1.5 py-0.5 rounded-full leading-none transition-colors">
                    {{ $apps->count() }}
                </span>
            </button>
            @endforeach
            {{-- 報告済タブ --}}
            <button
                @click="tab = '報告済'"
                :class="tab === '報告済'
                    ? 'border-b-2 border-pink-500 text-pink-600 font-semibold'
                    : 'text-gray-500'"
                class="flex-1 min-w-0 py-2.5 px-1 text-xs whitespace-nowrap flex flex-col items-center gap-0.5 transition-colors">
                <span>報告済</span>
                <span
                    :class="tab === '報告済' ? 'bg-pink-500 text-white' : 'bg-gray-200 text-gray-600'"
                    class="text-xs font-bold px-1.5 py-0.5 rounded-full leading-none transition-colors">
                    {{ $monitorReports->count() }}
                </span>
            </button>
            {{-- 回収タブ --}}
            <button
                @click="tab = '回収'"
                :class="tab === '回収'
                    ? 'border-b-2 border-pink-500 text-pink-600 font-semibold'
                    : 'text-gray-500'"
                class="flex-1 min-w-0 py-2.5 px-1 text-xs whitespace-nowrap flex flex-col items-center gap-0.5 transition-colors">
                <span>回収</span>
                <span
                    :class="tab === '回収' ? 'bg-pink-500 text-white' : 'bg-gray-200 text-gray-600'"
                    class="text-xs font-bold px-1.5 py-0.5 rounded-full leading-none transition-colors">
                    {{ $collectionReports->count() }}
                </span>
            </button>
        </div>

        {{-- 応募中・実施完了・キャンセル タブコンテンツ --}}
        @php
            $appCard = function ($app) {
                return '
                <div class="px-4 py-3 flex items-center gap-3">
                    <div class="w-10 h-10 bg-pink-50 rounded-lg flex-shrink-0 overflow-hidden">
                        ' . (($app->campaign && $app->campaign->thumbnail)
                            ? '<img src="' . asset('storage/' . $app->campaign->thumbnail) . '" class="w-full h-full object-cover">'
                            : '<div class="w-full h-full flex items-center justify-center text-lg">💄</div>') . '
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">' . e($app->campaign->title ?? '削除済み案件') . '</p>
                        <p class="text-xs text-gray-400 mt-0.5">応募：' . $app->applied_at->format('Y/m/d') . '</p>
                    </div>
                    ' . ($app->campaign ? '<a href="' . route('member.campaigns.show', $app->campaign) . '" class="text-xs text-pink-500 flex-shrink-0">詳細 →</a>' : '') . '
                </div>';
            };
        @endphp
        @foreach($groups as $label => $apps)
        <div x-show="tab === '{{ $label }}'" x-cloak>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                @if($label === '応募中')
                    @if($applyingActive->isEmpty() && $applyingEnded->isEmpty())
                        <p class="text-xs text-gray-400 text-center py-8">対象の履歴がありません</p>
                    @else
                        @if($applyingActive->isEmpty())
                            <p class="text-xs text-gray-400 text-center py-6">応募中の案件はありません</p>
                        @else
                            <div class="divide-y divide-gray-50">
                                @foreach($applyingActive as $app){!! $appCard($app) !!}@endforeach
                            </div>
                        @endif
                    @endif
                @else
                    @if($apps->isEmpty())
                        <p class="text-xs text-gray-400 text-center py-8">対象の履歴がありません</p>
                    @else
                        <div class="divide-y divide-gray-50">
                            @foreach($apps as $app){!! $appCard($app) !!}@endforeach
                        </div>
                    @endif
                @endif
            </div>

            @if($label === '応募中' && $applyingEnded->isNotEmpty())
            <details class="mt-3 bg-gray-50 rounded-xl border border-gray-100 overflow-hidden">
                <summary class="px-4 py-3 text-xs font-medium text-gray-500 cursor-pointer select-none flex items-center justify-between">
                    <span>募集終了（{{ $applyingEnded->count() }}件）</span>
                    <span class="text-gray-400">タップして開く</span>
                </summary>
                <div class="divide-y divide-gray-100 bg-white">
                    @foreach($applyingEnded as $app){!! $appCard($app) !!}@endforeach
                </div>
            </details>
            @endif
        </div>
        @endforeach

        {{-- 報告済タブコンテンツ（MonitorReportベース） --}}
        <div x-show="tab === '報告済'" x-cloak>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                @if($monitorReports->isEmpty())
                    <p class="text-xs text-gray-400 text-center py-8">報告履歴がありません</p>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach($monitorReports as $report)
                        @php
                            $badge = match($report->status) {
                                'pending'  => ['label' => '承認待ち',  'color' => 'bg-blue-100 text-blue-600'],
                                'approved' => ['label' => '支払い待ち', 'color' => 'bg-yellow-100 text-yellow-700'],
                                'rejected' => ['label' => '差戻し',    'color' => 'bg-red-100 text-red-600'],
                                default    => null,
                            };
                            if ($report->status === 'approved' && $report->payment_status === 'paid') {
                                $badge = ['label' => '支払い済', 'color' => 'bg-teal-100 text-teal-700'];
                            }
                        @endphp
                        <div class="px-4 py-3 flex items-center gap-3">
                            <div class="w-10 h-10 bg-pink-50 rounded-lg flex-shrink-0 overflow-hidden">
                                @if($report->campaign?->thumbnail)
                                    <img src="{{ asset('storage/' . $report->campaign->thumbnail) }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-lg">💄</div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">
                                    {{ $report->campaign->title ?? ($report->purchase_type === 'other' ? 'その他報告' : '削除済み案件') }}
                                </p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <p class="text-xs text-gray-400">報告：{{ $report->created_at->format('Y/m/d') }}</p>
                                    @if($badge)
                                        <span class="text-xs px-1.5 py-0.5 rounded-full {{ $badge['color'] }}">{{ $badge['label'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('member.reports.show', $report) }}"
                               class="text-xs text-pink-500 flex-shrink-0">
                                詳細 →
                            </a>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- 回収タブコンテンツ --}}
        <div x-show="tab === '回収'" x-cloak>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                @if($collectionReports->isEmpty())
                    <p class="text-xs text-gray-400 text-center py-8">回収申請がありません</p>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach($collectionReports as $cr)
                        @php
                            $campaigns = $cr->campaigns();
                            $statusBadge = match($cr->status) {
                                'pending'  => ['label' => '承認待ち', 'color' => 'bg-yellow-100 text-yellow-700'],
                                'approved' => ['label' => '承認',     'color' => 'bg-green-100 text-green-700'],
                                'rejected' => ['label' => '差戻し',   'color' => 'bg-red-100 text-red-700'],
                                default    => ['label' => $cr->status, 'color' => 'bg-gray-100 text-gray-700'],
                            };
                        @endphp
                        <div class="px-4 py-3">
                            <div class="flex items-center gap-3 mb-1">
                                <div class="w-10 h-10 bg-blue-50 rounded-lg flex-shrink-0 flex items-center justify-center text-xl">📦</div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800">
                                        回収サービス（{{ $cr->item_count }}点）
                                    </p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <p class="text-xs text-gray-400">{{ $cr->created_at->format('Y/m/d') }}申請</p>
                                        <span class="text-xs px-1.5 py-0.5 rounded-full {{ $statusBadge['color'] }}">
                                            {{ $statusBadge['label'] }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-sm font-bold text-pink-600">¥{{ number_format($cr->totalFee()) }}</p>
                                </div>
                            </div>
                            <div class="ml-13 pl-1 flex items-end justify-between">
                                <div>
                                    @foreach($campaigns as $c)
                                    <p class="text-xs text-gray-500">・{{ $c->title }}</p>
                                    @endforeach
                                </div>
                                <a href="{{ route('member.reports.show_collection', $cr) }}"
                                   class="text-xs text-pink-500 flex-shrink-0">
                                    詳細 →
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
