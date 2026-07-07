@extends('layouts.admin')

@section('title', 'ダッシュボード')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')

{{-- アラート --}}
@if(!empty($alerts))
<div class="space-y-2 mb-5">
    @foreach($alerts as $alert)
    <div class="flex items-center gap-3 px-4 py-2.5 rounded-lg border
        {{ $alert['level'] === 'error' ? 'bg-red-50 border-red-300 text-red-800' : 'bg-yellow-50 border-yellow-300 text-yellow-800' }}">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1 text-sm">
            @if(!empty($alert['campaign_name']) && !empty($alert['campaign_link']))
                <a href="{{ $alert['campaign_link'] }}" class="font-bold underline mr-1">{{ $alert['campaign_name'] }}</a>
            @endif
            {{ $alert['message'] }}
        </div>
        @if(!empty($alert['link']))
        <a href="{{ $alert['link'] }}" class="shrink-0 text-xs font-medium underline">{{ $alert['label'] }}</a>
        @endif
        <form action="{{ route('admin.alerts.dismiss') }}" method="POST" class="shrink-0">
            @csrf
            <input type="hidden" name="alert_key" value="{{ $alert['dismiss_key'] ?? '' }}">
            <button type="submit" class="text-xs px-2 py-0.5 border border-current rounded opacity-50 hover:opacity-100 transition-opacity">無視</button>
        </form>
    </div>
    @endforeach
</div>
@endif

<div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold text-gray-800">ダッシュボード</h1>
    {{-- 月次/累計 + 月セレクト --}}
    <form method="GET" class="flex items-center gap-2">
        <select name="mode" onchange="this.form.submit()"
                class="border rounded px-2 py-1.5 text-sm bg-white">
            <option value="monthly" @selected($mode === 'monthly')>月次</option>
            <option value="cumulative" @selected($mode === 'cumulative')>累計</option>
        </select>
        @if($mode === 'monthly')
        <select name="month_key" onchange="syncMonth(this); this.form.submit()"
                class="border rounded px-2 py-1.5 text-sm bg-white">
            @foreach($months as $m)
                <option value="{{ $m['year'] }}-{{ $m['month'] }}"
                    @selected($m['year'] === $year && $m['month'] === $month)
                    @disabled(!($m['has_data'] ?? true))>
                    {{ $m['label'] }}
                </option>
            @endforeach
        </select>
        <input type="hidden" name="year"  id="inp-year"  value="{{ $year }}">
        <input type="hidden" name="month" id="inp-month" value="{{ $month }}">
        @endif
    </form>
</div>

{{-- 承認待ちアラート --}}
@if($pendingReportsCount > 0)
<div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-4 mb-5 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <span class="text-yellow-500 text-xl">⚠️</span>
        <div>
            <p class="font-bold text-yellow-800">モニター報告 承認待ち</p>
            <p class="text-sm text-yellow-700">
                {{ $pendingReportsCount }}件　¥{{ number_format($pendingReportsAmount) }}
                （モニター経費＋協力金 合計）
            </p>
        </div>
    </div>
    <a href="{{ route('admin.reports.index') }}"
       class="bg-pink-500 text-white px-4 py-1.5 rounded text-sm hover:bg-pink-600">
        報告を確認する
    </a>
</div>
@endif

{{-- メイン指標 --}}
@php
function diffBadge(int $current, int $prev): string {
    $diff = $current - $prev;
    if ($diff > 0) return '<span class="text-xs text-green-600 font-medium ml-1">+' . number_format($diff) . '↑</span>';
    if ($diff < 0) return '<span class="text-xs text-red-500 font-medium ml-1">' . number_format($diff) . '↓</span>';
    return '<span class="text-xs text-gray-700 ml-1">±0</span>';
}
$m  = $metrics;
$pm = $prevMetrics;
@endphp

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
    @php
    $cards = [
        ['label' => '会員数',   'value' => $m['members'],       'prev' => $pm['members'],       'prefix' => '',  'suffix' => '名', 'color' => 'text-pink-600'],
        ['label' => '応募数',   'value' => $m['applied'],        'prev' => $pm['applied'],        'prefix' => '',  'suffix' => '件', 'color' => 'text-purple-600'],
        ['label' => '実施数',   'value' => $m['completed'],      'prev' => $pm['completed'],      'prefix' => '',  'suffix' => '件', 'color' => 'text-blue-600'],
        ['label' => '報告数',   'value' => $m['reported'],       'prev' => $pm['reported'],       'prefix' => '',  'suffix' => '件', 'color' => 'text-indigo-600'],
        ['label' => '承認数',   'value' => $m['approvedCount'],  'prev' => $pm['approvedCount'],  'prefix' => '',  'suffix' => '件', 'color' => 'text-green-600'],
    ];
    @endphp
    @foreach($cards as $card)
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-700 mb-1">{{ $card['label'] }}</p>
        <p class="text-2xl font-bold {{ $card['color'] }}">
            {{ $card['prefix'] }}{{ number_format($card['value']) }}{{ $card['suffix'] }}
        </p>
        {!! diffBadge($card['value'], $card['prev']) !!}
    </div>
    @endforeach
</div>

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    @php
    $cards2 = [
        ['label' => '協力金',     'value' => $m['cooperationFee'], 'prev' => $pm['cooperationFee'], 'suffix' => ''],
        ['label' => '売上',       'value' => $m['sales'],          'prev' => $pm['sales'],          'suffix' => ''],
        ['label' => '漏れ経費',   'value' => $m['leakCost'],       'prev' => $pm['leakCost'],       'suffix' => ''],
        ['label' => '全否認',     'value' => $m['allDenied'],      'prev' => $pm['allDenied'],      'suffix' => ''],
        ['label' => '粗利',       'value' => $m['grossProfit'],    'prev' => $pm['grossProfit'],    'suffix' => ''],
    ];
    @endphp
    @foreach($cards2 as $card)
    @php $isNegative = $card['value'] < 0; @endphp
    <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-700 mb-1">{{ $card['label'] }}</p>
        <p class="text-2xl font-bold {{ $isNegative ? 'text-red-600' : ($card['label'] === '粗利' ? 'text-green-600' : 'text-gray-800') }}">
            ¥{{ number_format($card['value']) }}
        </p>
        {!! diffBadge($card['value'], $card['prev']) !!}
    </div>
    @endforeach
</div>

{{-- グラフ --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-sm font-bold text-gray-700 mb-3">売上・協力金推移（直近12ヶ月）</h3>
        <canvas id="chart-sales" height="200"></canvas>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-sm font-bold text-gray-700 mb-3">粗利・承認率推移（直近12ヶ月）</h3>
        <canvas id="chart-gross" height="200"></canvas>
    </div>
</div>

<script>
function syncMonth(sel) {
    const [y, m] = sel.value.split('-');
    document.getElementById('inp-year').value  = y;
    document.getElementById('inp-month').value = m;
}

const chartData = @json($chartData);

// 売上・協力金
new Chart(document.getElementById('chart-sales'), {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: [
            {
                label: '売上',
                data: chartData.sales,
                borderColor: '#ec4899',
                backgroundColor: 'rgba(236,72,153,0.08)',
                tension: 0.3,
                fill: true,
            },
            {
                label: '協力金',
                data: chartData.fees,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139,92,246,0.08)',
                tension: 0.3,
                fill: true,
            },
        ],
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: {
            y: {
                ticks: { callback: v => '¥' + v.toLocaleString() },
            }
        }
    }
});

// 粗利・承認率
new Chart(document.getElementById('chart-gross'), {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: [
            {
                label: '粗利',
                data: chartData.grossArr,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.08)',
                tension: 0.3,
                fill: true,
                yAxisID: 'y',
            },
            {
                label: '承認率(%)',
                data: chartData.approvals,
                borderColor: '#f59e0b',
                borderDash: [4, 4],
                tension: 0.3,
                fill: false,
                yAxisID: 'y2',
            },
        ],
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: {
            y:  { ticks: { callback: v => '¥' + v.toLocaleString() } },
            y2: { position: 'right', min: 0, max: 100, ticks: { callback: v => v + '%' }, grid: { drawOnChartArea: false } },
        }
    }
});
</script>

{{-- 月次→年・月のhidden同期 --}}
<script>
document.querySelectorAll('select[name="month_key"]').forEach(sel => {
    sel.addEventListener('change', function() {
        const [y, m] = this.value.split('-');
        const form = this.closest('form');
        form.querySelector('input[name="year"]').value  = y;
        form.querySelector('input[name="month"]').value = m;
    });
});
</script>
@endsection

