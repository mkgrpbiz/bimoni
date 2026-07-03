@extends('layouts.admin')

@section('title', '承認反映管理')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-2xl font-bold text-gray-800">承認反映管理</h1>

    <div class="flex items-center gap-3">
        <button onclick="saveSortOrder(this)"
                class="text-xs bg-indigo-500 text-white px-3 py-1.5 rounded hover:bg-indigo-600">
            並び順を保存
        </button>

        <form method="GET" class="flex items-center gap-2">
            <select name="mode" onchange="this.form.submit()"
                    class="border rounded px-2 py-1.5 text-sm bg-white">
                <option value="monthly"    @selected($mode === 'monthly')>月次</option>
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
</div>

@if(session('success'))
<div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-800 text-xs">
            <tr>
                <th class="px-2 py-3 w-8"></th>
                <th class="px-4 py-3 text-left">案件名</th>
                <th class="px-3 py-3 text-center">実施数</th>
                <th class="px-3 py-3 text-center">報告数</th>
                <th class="px-3 py-3 text-center">反映数<br><span class="font-normal text-gray-700">（編集可）</span></th>
                <th class="px-3 py-3 text-right">売上</th>
                <th class="px-3 py-3 text-right">粗利</th>
                <th class="px-3 py-3 text-center">全否認</th>
                <th class="px-3 py-3 text-center">表示</th>
            </tr>
        </thead>
        <tbody id="campaign-tbody" class="divide-y">
            @php
            $totalSales = 0;
            $totalGross = 0;
            $totalCompleted = 0;
            $totalReported  = 0;
            $totalReflected = 0;
            @endphp

            @foreach($campaigns as $campaign)
            @php
            $ref     = $reflections->get($campaign->id);
            $appStat = $applicationStats->get($campaign->id);
            $completedCount = $appStat?->completed_count ?? 0;
            $approvedCount  = $appStat?->approved_count ?? 0;
            $reflectCount   = $ref?->reflection_count ?? 0;
            $isAllDenied    = $ref?->is_all_denied ?? false;
            $sales          = $reflectCount * ($campaign->campaign_unit_price ?? 0);
            $gross          = $reflectCount * ($campaign->gross_profit ?? 0);
            @endphp
            @if($completedCount >= 1)
            @php
            $totalSales     += $sales;
            $totalGross     += $gross;
            $totalCompleted += $completedCount;
            $totalReported  += $approvedCount;
            $totalReflected += $reflectCount;
            @endphp
            <tr class="hover:bg-gray-50 {{ $isAllDenied ? 'bg-red-50' : '' }} {{ $campaign->is_visible ? '' : 'opacity-50' }}"
                data-campaign="{{ $campaign->id }}"
                data-unit-price="{{ (int)($campaign->campaign_unit_price ?? 0) }}"
                data-gross-profit="{{ (int)($campaign->gross_profit ?? 0) }}">
                <td class="px-2 py-3 text-center text-gray-400 drag-handle cursor-grab select-none text-base">≡</td>
                <td class="px-4 py-3">
                    <span class="font-medium text-gray-800">{{ $campaign->title }}</span>
                    @if(!$campaign->is_visible)
                        <span class="ml-1 text-xs text-gray-700">（非表示）</span>
                    @endif
                    @if($isAllDenied)
                        <span class="ml-1 text-xs bg-red-500 text-white px-1.5 py-0.5 rounded">全否認</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-center text-gray-800">{{ $completedCount }}</td>
                <td class="px-3 py-3 text-center text-gray-800">{{ $approvedCount }}</td>
                <td class="px-3 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <input type="number" min="0"
                               class="reflection-input border rounded px-2 py-1 text-center text-sm w-20"
                               value="{{ $reflectCount }}"
                               data-campaign="{{ $campaign->id }}"
                               data-year="{{ $year }}"
                               data-month="{{ $month }}">
                        <button onclick="saveReflection({{ $campaign->id }}, this)"
                                class="text-xs bg-pink-500 text-white px-2 py-1 rounded hover:bg-pink-600">保存</button>
                    </div>
                </td>
                <td class="px-3 py-3 text-right font-medium cell-sales">¥{{ number_format($sales) }}</td>
                <td class="px-3 py-3 text-right font-medium cell-gross {{ $gross < 0 ? 'text-red-600' : 'text-green-700' }}">¥{{ number_format($gross) }}</td>
                <td class="px-3 py-3 text-center">
                    <button onclick="openDeniedModal({{ $campaign->id }}, {{ $year }}, {{ $month }}, {{ $isAllDenied ? 'true' : 'false' }}, '{{ addslashes($campaign->title) }}')"
                            class="text-xs {{ $isAllDenied ? 'bg-red-500 text-white' : 'bg-gray-500 text-white' }} px-2 py-1 rounded hover:opacity-80">
                        {{ $isAllDenied ? '全否認中' : '全否認' }}
                    </button>
                </td>
                <td class="px-3 py-3 text-center">
                    <button onclick="toggleVisible({{ $campaign->id }}, {{ $campaign->is_visible ? 'true' : 'false' }}, this)"
                            class="text-xs {{ $campaign->is_visible ? 'bg-green-600 text-white' : 'bg-gray-500 text-white' }} px-2 py-1 rounded hover:opacity-80">
                        {{ $campaign->is_visible ? '表示中' : '非表示' }}
                    </button>
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-gray-50 font-bold text-sm border-t">
                <td></td>
                <td class="px-4 py-3">合計</td>
                <td class="px-3 py-3 text-center">{{ $totalCompleted }}</td>
                <td class="px-3 py-3 text-center">{{ $totalReported }}</td>
                <td class="px-3 py-3 text-center">{{ $totalReflected }}</td>
                <td class="px-3 py-3 text-right" id="total-sales">¥{{ number_format($totalSales) }}</td>
                <td class="px-3 py-3 text-right {{ $totalGross < 0 ? 'text-red-600' : 'text-green-700' }}" id="total-gross">¥{{ number_format($totalGross) }}</td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- 全否認モーダル --}}
<div id="denied-modal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl p-6 w-80">
        <h3 class="font-bold text-gray-800 mb-2">全否認フラグの切り替え</h3>
        <p class="text-sm text-gray-800 mb-1" id="denied-campaign-name"></p>
        <p class="text-sm text-gray-800 mb-4" id="denied-current-state"></p>
        <div class="flex gap-2">
            <button onclick="confirmToggleDenied()"
                    class="flex-1 bg-pink-500 text-white py-2 rounded hover:bg-pink-600 text-sm">確定</button>
            <button onclick="document.getElementById('denied-modal').classList.add('hidden')"
                    class="flex-1 bg-gray-500 text-white py-2 rounded hover:bg-gray-600 text-sm">キャンセル</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
new Sortable(document.getElementById('campaign-tbody'), {
    handle: '.drag-handle',
    animation: 150,
    ghostClass: 'bg-blue-50',
});

function updateTotals() {
    let totalSales = 0, totalGross = 0;
    document.querySelectorAll('#campaign-tbody tr[data-campaign]').forEach(row => {
        const salesText = row.querySelector('.cell-sales')?.textContent ?? '¥0';
        const grossText = row.querySelector('.cell-gross')?.textContent ?? '¥0';
        totalSales += parseInt(salesText.replace(/[¥,]/g, '')) || 0;
        totalGross += parseInt(grossText.replace(/[¥,]/g, '')) || 0;
    });
    document.getElementById('total-sales').textContent = '¥' + totalSales.toLocaleString();
    const tg = document.getElementById('total-gross');
    tg.textContent = '¥' + totalGross.toLocaleString();
    tg.className = tg.className.replace(/text-(red|green)-\d+/, '');
    tg.classList.add(totalGross < 0 ? 'text-red-600' : 'text-green-700');
}

function syncMonth(sel) {
    const [y, m] = sel.value.split('-');
    document.getElementById('inp-year').value  = y;
    document.getElementById('inp-month').value = m;
}

async function saveReflection(campaignId, btn) {
    const row   = btn.closest('tr');
    const input = row.querySelector('.reflection-input');
    const count = parseInt(input.value);
    const year  = input.dataset.year;
    const month = input.dataset.month;

    btn.disabled = true;
    btn.textContent = '…';

    const res = await fetch(`/admin/approval-reflections/${campaignId}`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ year, month, reflection_count: count }),
    });

    if (res.ok) {
        const unitPrice   = parseInt(row.dataset.unitPrice)   || 0;
        const grossProfit = parseInt(row.dataset.grossProfit) || 0;
        const sales = count * unitPrice;
        const gross = count * grossProfit;

        row.querySelector('.cell-sales').textContent = '¥' + sales.toLocaleString();
        const grossCell = row.querySelector('.cell-gross');
        grossCell.textContent = '¥' + gross.toLocaleString();
        grossCell.className = grossCell.className.replace(/text-(red|green)-\d+/, '');
        grossCell.classList.add(gross < 0 ? 'text-red-600' : 'text-green-700');

        updateTotals();

        btn.textContent = '✓';
        btn.classList.replace('bg-pink-500', 'bg-green-500');
        setTimeout(() => { btn.textContent = '保存'; btn.classList.replace('bg-green-500', 'bg-pink-500'); btn.disabled = false; }, 1500);
    } else {
        btn.textContent = 'エラー'; btn.disabled = false;
    }
}

let _deniedCampaign = null, _deniedYear = null, _deniedMonth = null;

function openDeniedModal(campaignId, year, month, current, title) {
    _deniedCampaign = campaignId; _deniedYear = year; _deniedMonth = month;
    document.getElementById('denied-campaign-name').textContent = title;
    document.getElementById('denied-current-state').textContent = current ? '現在: 全否認中 → 解除します' : '現在: 通常 → 全否認フラグを立てます';
    document.getElementById('denied-modal').classList.remove('hidden');
}

async function confirmToggleDenied() {
    document.getElementById('denied-modal').classList.add('hidden');
    const res = await fetch(`/admin/approval-reflections/${_deniedCampaign}/toggle-denied`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ year: _deniedYear, month: _deniedMonth }),
    });
    if (res.ok) location.reload();
}

function toggleVisible(campaignId, current, btn) {
    if (!confirm(current ? 'この案件を非表示にしますか？' : 'この案件を表示にしますか？')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/admin/campaigns/${campaignId}/toggle-visible`;
    form.innerHTML = `<input name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}"><input name="_method" value="PATCH">`;
    document.body.appendChild(form);
    form.submit();
}

async function saveSortOrder(btn) {
    const rows = document.querySelectorAll('#campaign-tbody tr[data-campaign]');
    const ids  = Array.from(rows).map(r => parseInt(r.dataset.campaign));
    const orig = btn.textContent;
    btn.disabled = true;
    btn.textContent = '保存中…';

    const res = await fetch('/admin/campaigns/reorder', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ ids }),
    });

    if (res.ok) {
        btn.textContent = '✓ 保存しました';
        btn.classList.replace('bg-indigo-500', 'bg-green-500');
        setTimeout(() => { btn.textContent = orig; btn.classList.replace('bg-green-500', 'bg-indigo-500'); btn.disabled = false; }, 2000);
    } else {
        btn.textContent = 'エラー'; btn.disabled = false;
    }
}
</script>
@endsection
