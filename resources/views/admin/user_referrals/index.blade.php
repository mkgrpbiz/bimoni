@extends('layouts.admin')

@section('title', '招待報酬管理（ユーザー）')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">招待報酬管理（ユーザー）</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-800 rounded px-4 py-2 mb-4">{{ session('success') }}</div>
@endif

<form method="GET" class="bg-white rounded-lg shadow p-3 mb-4 flex flex-wrap gap-3 items-center">
    <select name="month_key" onchange="syncMonth(this); this.form.submit()"
            class="border rounded px-2 py-1.5 text-sm bg-white">
        @foreach($months as $m)
            <option value="{{ $m['year'] }}-{{ $m['month'] }}"
                @selected($m['year'] === $year && $m['month'] === $mon)>
                {{ $m['label'] }}
            </option>
        @endforeach
    </select>
    <input type="hidden" name="year"  id="inp-year"  value="{{ $year }}">
    <input type="hidden" name="month" id="inp-month" value="{{ $mon }}">
    <a href="{{ route('admin.user_referrals.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded text-sm hover:bg-gray-500">リセット</a>
</form>

<script>
function syncMonth(sel) {
    const [y, m] = sel.value.split('-');
    document.getElementById('inp-year').value  = y;
    document.getElementById('inp-month').value = m;
}
</script>

<div class="grid grid-cols-2 gap-4 mb-4">
    <div class="bg-white rounded-lg shadow px-5 py-4">
        <p class="text-xs text-gray-400 mb-1">先月 招待報酬合計</p>
        <p class="text-2xl font-bold text-gray-600">¥{{ number_format($prevTotal) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $month->copy()->subMonth()->format('Y年n月') }}</p>
    </div>
    <div class="bg-white rounded-lg shadow px-5 py-4 border-l-4 border-pink-400">
        <p class="text-xs text-gray-400 mb-1">当月 招待報酬合計</p>
        <p class="text-2xl font-bold text-pink-600">¥{{ number_format($currentTotal) }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $month->format('Y年n月') }}</p>
    </div>
</div>

<p class="text-xs text-gray-400 mb-2">支払い状況は「ポイント還元管理」の予約済み・支払済み処理と連動しています。</p>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm whitespace-nowrap">
        <thead class="bg-gray-50 text-gray-700">
            <tr>
                <th class="px-4 py-3 text-left">招待者</th>
                <th class="px-4 py-3 text-left">ユーザーID</th>
                <th class="px-4 py-3 text-right">招待人数（累計）</th>
                <th class="px-4 py-3 text-right">今月の初回利用数</th>
                <th class="px-4 py-3 text-right">招待報酬合計</th>
                <th class="px-4 py-3 text-center">ステータス</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($summary as $row)
            <tr class="even:bg-gray-50 hover:bg-gray-100">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $row['referrer']->name ?? '（削除済みユーザー）' }}</td>
                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $row['referrer']->bimoni_user_id ?? '-' }}</td>
                <td class="px-4 py-3 text-right">{{ $row['referral_count'] }}</td>
                <td class="px-4 py-3 text-right">{{ $row['reward_count'] }}</td>
                <td class="px-4 py-3 text-right font-bold text-green-600">¥{{ number_format($row['total']) }}</td>
                <td class="px-4 py-3 text-center">
                    @if($row['status'] === 'pending')
                        <span class="inline-block bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded font-medium">予約待ち</span>
                    @elseif($row['status'] === 'paid')
                        <span class="inline-block bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded font-medium">支払い済</span>
                    @else
                        <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-1 rounded font-medium">予約済</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                    {{ $month->format('Y年n月') }}の招待報酬データがありません
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
