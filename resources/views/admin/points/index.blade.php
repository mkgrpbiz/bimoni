@extends('layouts.admin')

@section('title', '協力金管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">協力金管理</h1>
    <button onclick="document.getElementById('adjust-form').classList.toggle('hidden')"
            class="bg-gray-500 text-white px-3 py-1.5 rounded text-sm hover:bg-gray-600">手動調整</button>
</div>

{{-- 手動調整フォーム --}}
<div id="adjust-form" class="hidden bg-white rounded-lg shadow p-5 mb-5">
    <h2 class="font-bold text-gray-700 mb-4">手動ポイント調整</h2>
    <form method="POST" action="{{ route('admin.points.adjust') }}" class="flex flex-wrap gap-3 items-end">
        @csrf
        <div>
            <label class="block text-xs text-gray-500 mb-1">ユーザーID</label>
            <input type="text" name="bimoni_user_id" required placeholder="BMN010001"
                   value="{{ old('bimoni_user_id') }}"
                   class="border rounded px-3 py-2 text-sm w-44 @error('bimoni_user_id') border-red-400 @enderror">
            @error('bimoni_user_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">金額（マイナスも可）</label>
            <input type="number" name="amount" required placeholder="例: 500"
                   value="{{ old('amount') }}"
                   class="border rounded px-3 py-2 text-sm w-36 @error('amount') border-red-400 @enderror">
            @error('amount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex-1 min-w-40">
            <label class="block text-xs text-gray-500 mb-1">理由</label>
            <input type="text" name="reason" required placeholder="調整理由"
                   value="{{ old('reason') }}"
                   class="w-full border rounded px-3 py-2 text-sm @error('reason') border-red-400 @enderror">
            @error('reason')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">実行</button>
    </form>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- 先月・当月ブロック --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    @foreach($blocks as $i => $block)
    <div class="bg-white rounded-xl shadow p-5 {{ $i === 1 ? 'border-l-4 border-pink-400' : '' }}">
        <p class="text-xs text-gray-400 mb-0.5">{{ $i === 0 ? '先月' : '当月' }}</p>
        <h2 class="text-base font-bold text-gray-700 mb-3">{{ $block['month']->format('Y年n月') }}</h2>

        <div class="flex items-end gap-4 mb-3">
            <div>
                <p class="text-xs text-gray-400 mb-0.5">合計</p>
                <p class="text-2xl font-bold {{ $i === 1 ? 'text-pink-600' : 'text-gray-700' }}">¥{{ number_format($block['total']) }}</p>
                <p class="text-xs text-gray-400">{{ $block['count'] }}件</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">ステータス</p>
                @if($block['count'] === 0)
                    <span class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-400 text-xs font-medium px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>予約不要
                    </span>
                @elseif($block['hasPending'])
                    <span class="inline-flex items-center gap-1.5 bg-yellow-100 text-yellow-700 text-xs font-medium px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span>予約待ち
                    </span>
                @elseif($block['allPaid'])
                    <span class="inline-flex items-center gap-1.5 bg-blue-100 text-blue-700 text-xs font-medium px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>支払い済
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 bg-green-100 text-green-700 text-xs font-medium px-2.5 py-1 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>予約済
                    </span>
                @endif
            </div>
        </div>

        <div class="flex gap-2 flex-wrap items-end">
            <form method="GET" action="{{ route('admin.points.zengin') }}" class="flex items-end gap-1.5">
                <input type="hidden" name="month" value="{{ $block['month']->format('Y-m') }}">
                <div>
                    <label class="block text-xs text-gray-400 mb-0.5">振込日</label>
                    <input type="date" name="transfer_date" required value="{{ now()->format('Y-m-d') }}"
                           class="border rounded px-2 py-1 text-xs">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-3 py-1.5 rounded text-sm hover:bg-blue-700">全銀出力</button>
            </form>

            @if($block['hasPending'] && $block['total'] > 0)
            <form method="POST" action="{{ route('admin.points.mark_reserved') }}"
                  onsubmit="return confirm('{{ $block['month']->format('Y年n月') }}の協力金をすべて予約済にしますか？')">
                @csrf @method('PATCH')
                <input type="hidden" name="month" value="{{ $block['month']->format('Y-m') }}">
                <button type="submit" class="bg-green-500 text-white px-3 py-1.5 rounded text-sm hover:bg-green-600">→ 予約済にする</button>
            </form>
            @elseif(!$block['hasPending'] && $block['total'] > 0)
            <form method="POST" action="{{ route('admin.points.mark_paid') }}"
                  onsubmit="return confirm('{{ $block['month']->format('Y年n月') }}の協力金をすべて支払い済にしますか？')">
                @csrf @method('PATCH')
                <input type="hidden" name="month" value="{{ $block['month']->format('Y-m') }}">
                <button type="submit" class="bg-blue-600 text-white px-3 py-1.5 rounded text-sm hover:bg-blue-700">→ 支払い済にする</button>
            </form>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- 月別詳細 --}}
<form method="GET" class="bg-white rounded-lg shadow p-3 mb-4 flex flex-wrap gap-3 items-center">
    <select name="month_key" onchange="syncMonth(this); this.form.submit()"
            class="border rounded px-2 py-1.5 text-sm bg-white">
        @foreach($months as $m)
            <option value="{{ $m['year'] }}-{{ $m['month'] }}"
                @selected($m['year'] === $year && $m['month'] === $mon)
                @disabled(!($m['has_data'] ?? true))>
                {{ $m['label'] }}
            </option>
        @endforeach
    </select>
    <input type="hidden" name="year"  id="inp-year"  value="{{ $year }}">
    <input type="hidden" name="month" id="inp-month" value="{{ $mon }}">
    <input type="text" name="q" value="{{ request('q') }}"
           placeholder="ユーザーID/LINE名/氏名/フリガナ"
           class="border rounded px-2 py-1 text-sm w-52">
    <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">絞り込み</button>
    <a href="{{ route('admin.points.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded text-sm hover:bg-gray-500">リセット</a>
</form>

<script>
function syncMonth(sel) {
    const [y, m] = sel.value.split('-');
    document.getElementById('inp-year').value  = y;
    document.getElementById('inp-month').value = m;
}
</script>

<div class="bg-white rounded-lg shadow mb-2 px-4 py-3 text-sm text-gray-600 flex items-center gap-4">
    <span>{{ $month->format('Y年n月') }} 合計: <strong>¥{{ number_format($totalAmount) }}</strong></span>
    <form method="POST" action="{{ route('admin.points.mark_paid') }}"
          onsubmit="return confirm('{{ $month->format('Y年n月') }}の協力金をすべて支払い済にしますか？')">
        @csrf @method('PATCH')
        <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
        <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">→ 支払い済にする</button>
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm whitespace-nowrap">
        <thead class="bg-gray-50 text-gray-700">
            <tr>
                <th class="px-4 py-3 text-left">ユーザーID</th>
                <th class="px-4 py-3 text-left">LINE表示名</th>
                <th class="px-4 py-3 text-left">名前</th>
                <th class="px-4 py-3 text-left">フリガナ</th>
                <th class="px-4 py-3 text-right">モニター件数</th>
                <th class="px-4 py-3 text-right">回収件数</th>
                <th class="px-4 py-3 text-right">協力金合計</th>
                <th class="px-4 py-3 text-center">ステータス</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($userSummary as $row)
            @php $u = $row['user']; @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $u?->bimoni_user_id ?? '-' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $u?->line_display_name ?? '-' }}</td>
                <td class="px-4 py-3 font-medium text-gray-800">{{ $u?->name ?? '-' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $u?->name_kana ?? '-' }}</td>
                <td class="px-4 py-3 text-right text-gray-600">{{ $row['monitorCount'] }}件</td>
                <td class="px-4 py-3 text-right text-blue-600">{{ $row['collectionCount'] }}件</td>
                <td class="px-4 py-3 text-right font-bold text-gray-800">¥{{ number_format($row['total']) }}</td>
                <td class="px-4 py-3 text-center">
                    @if($row['total'] === 0)
                        <span class="bg-gray-100 text-gray-400 text-xs px-2 py-0.5 rounded-full">予約不要</span>
                    @elseif($row['status'] === 'pending')
                        <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full">予約待ち</span>
                    @elseif($row['status'] === 'paid')
                        <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full">支払い済</span>
                    @else
                        <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full">予約済</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                    {{ $month->format('Y年n月') }}の承認済み報告はありません
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
