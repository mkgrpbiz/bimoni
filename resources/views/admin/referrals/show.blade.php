@extends('layouts.admin')

@section('title', '紹介報酬詳細')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.referrals.index', ['year' => $month->year, 'month' => $month->month]) }}"
       class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">← 紹介報酬管理</a>
    <h1 class="text-2xl font-bold text-gray-800">{{ $agent->name }}</h1>
</div>

<form method="GET" class="flex gap-3 items-end mb-4">
    <div>
        <label class="block text-xs text-gray-700 mb-1">月</label>
        <input type="month" name="month" value="{{ $month->format('Y-m') }}"
               class="border rounded px-2 py-1 text-sm">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">表示</button>
</form>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    {{-- 代理店情報 --}}
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 mb-3">代理店情報</h2>
        <dl class="text-sm space-y-1.5">
            <div class="flex gap-2">
                <dt class="text-gray-500 w-28 shrink-0">代理店名</dt>
                <dd class="font-medium text-gray-800">{{ $agent->name }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-500 w-28 shrink-0 pt-0.5">登録コード</dt>
                <dd>
                    @foreach($sortedCodes as $idx => $code)
                    <div class="{{ $idx > 0 ? 'ref-code-extra hidden' : '' }} flex items-center gap-2 mb-1 flex-wrap">
                        <span class="font-mono font-bold text-pink-600">{{ $code->code }}</span>
                        @if($code->label)<span class="text-xs text-gray-400">{{ $code->label }}</span>@endif
                        <span class="text-xs text-gray-400">{{ $userCounts[$code->code] ?? 0 }}名</span>
                        <button type="button" onclick="copyUrl('{{ route('invite', $code->code) }}')"
                                class="text-xs bg-pink-500 text-white px-2 py-0.5 rounded hover:bg-pink-600">コピー</button>
                    </div>
                    @endforeach
                    @if($sortedCodes->count() > 1)
                    <button type="button" id="toggle-ref-btn" onclick="toggleRefCodes(this, 'ref-code-extra', {{ $sortedCodes->count() - 1 }})"
                            class="text-xs text-pink-500 hover:underline">他{{ $sortedCodes->count() - 1 }}件を表示</button>
                    @endif
                    @foreach($childrenWithSortedCodes as $ci => $child)
                    <div class="mt-2 pt-2 border-t border-gray-100">
                        <p class="text-xs text-gray-500 mb-1">{{ $child->name }}</p>
                        @foreach($child->sortedCodes as $idx => $code)
                        <div class="{{ $idx > 0 ? 'child-code-extra-'.$ci.' hidden' : '' }} flex items-center gap-2 mb-1 flex-wrap">
                            <span class="font-mono font-bold text-pink-600">{{ $code->code }}</span>
                            @if($code->label)<span class="text-xs text-gray-400">{{ $code->label }}</span>@endif
                            <span class="text-xs text-gray-400">{{ $child->codeCounts[$code->code] ?? 0 }}名</span>
                            <button type="button" onclick="copyUrl('{{ route('invite', $code->code) }}')"
                                    class="text-xs bg-pink-500 text-white px-2 py-0.5 rounded hover:bg-pink-600">コピー</button>
                        </div>
                        @endforeach
                        @if($child->sortedCodes->count() > 1)
                        <button type="button" onclick="toggleRefCodes(this, 'child-code-extra-{{ $ci }}', {{ $child->sortedCodes->count() - 1 }})"
                                class="text-xs text-pink-500 hover:underline">他{{ $child->sortedCodes->count() - 1 }}件を表示</button>
                        @endif
                    </div>
                    @endforeach
                </dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-500 w-28 shrink-0">総登録人数</dt>
                <dd class="font-medium text-gray-800">{{ $referredUsers->count() }} 人</dd>
            </div>
        </dl>
    </div>

    {{-- 当月サマリー --}}
    @php
        $expectedPay = $reports->sum(fn($r) => $r->campaign?->referral_fee ?? 0);
    @endphp
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 mb-3">{{ $month->format('Y年n月') }} サマリー</h2>
        <dl class="text-sm space-y-1.5">
            <div class="flex justify-between">
                <dt class="text-gray-500">承認済み報告数</dt>
                <dd class="font-medium text-gray-800">{{ $reports->count() }} 件</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">紹介報酬合計</dt>
                <dd class="font-bold text-green-600">¥{{ number_format($expectedPay) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">ステータス</dt>
                <dd>
                    @if($expectedPay === 0)
                        <span class="bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded font-medium">処理不要</span>
                    @elseif($payStatus === 'done')
                        <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded font-medium">処理済</span>
                    @else
                        <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded font-medium">処理待ち</span>
                    @endif
                </dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">支払い日</dt>
                <dd class="text-gray-800">{{ $month->copy()->addMonth()->endOfMonth()->format('Y年n月末') }}</dd>
            </div>
        </dl>
    </div>
</div>

{{-- 登録コード別 承認済み報告一覧 --}}
<div class="bg-white rounded-lg shadow overflow-hidden mb-6">
    <div class="px-5 py-3 border-b flex items-center justify-between">
        <h2 class="font-bold text-gray-700">{{ $month->format('Y年n月') }} 承認済み報告詳細</h2>
        <span class="text-sm text-gray-500">{{ $reports->count() }}件</span>
    </div>
    @if($reports->isNotEmpty())
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-700">
            <tr>
                <th class="px-4 py-3 text-left">報告日</th>
                <th class="px-4 py-3 text-left">登録コード</th>
                <th class="px-4 py-3 text-left">ユーザーID</th>
                <th class="px-4 py-3 text-left">名前</th>
                <th class="px-4 py-3 text-left">案件名</th>
                <th class="px-4 py-3 text-right">協力金</th>
                <th class="px-4 py-3 text-right">紹介報酬</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($reports as $r)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-xs text-gray-500">{{ $r->created_at->format('Y/m/d') }}</td>
                <td class="px-4 py-3 font-mono text-xs text-pink-600">{{ $r->user?->referred_by_code ?? '-' }}</td>
                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $r->user?->bimoni_user_id ?? '-' }}</td>
                <td class="px-4 py-3 text-gray-800">{{ $r->user?->name ?? '-' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $r->campaign?->title ?? '-' }}</td>
                <td class="px-4 py-3 text-right text-gray-700">¥{{ number_format($r->campaign?->cooperation_fee ?? 0) }}</td>
                <td class="px-4 py-3 text-right font-bold text-green-600">¥{{ number_format($r->campaign?->referral_fee ?? 0) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-gray-50">
            <tr>
                <td colspan="6" class="px-4 py-3 text-right font-bold text-gray-700">紹介報酬合計</td>
                <td class="px-4 py-3 text-right font-bold text-green-600">¥{{ number_format($expectedPay) }}</td>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="px-4 py-8 text-center text-gray-400">{{ $month->format('Y年n月') }}の承認済み報告はありません</div>
    @endif
</div>

{{-- 登録者一覧（当月承認報告あり） --}}
@php
    $activeUsers = $referredUsers->filter(fn($ru) => $reports->where('user_id', $ru->id)->isNotEmpty());
@endphp
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-5 py-3 border-b">
        <h2 class="font-bold text-gray-700">{{ $month->format('Y年n月') }} 承認ユーザー一覧（{{ $activeUsers->count() }}人）</h2>
    </div>
    @if($activeUsers->isNotEmpty())
    <div class="overflow-x-auto">
    <table class="w-full text-sm whitespace-nowrap">
        <thead class="bg-gray-50 text-gray-700">
            <tr>
                <th class="px-3 py-3 text-left">登録日</th>
                <th class="px-3 py-3 text-left">登録コード</th>
                <th class="px-3 py-3 text-left">LINE表示名</th>
                <th class="px-3 py-3 text-left">名前</th>
                <th class="px-3 py-3 text-left">フリガナ</th>
                <th class="px-3 py-3 text-center">報告数<br><span class="font-normal text-xs">(¥500)</span></th>
                <th class="px-3 py-3 text-center">報告数<br><span class="font-normal text-xs">(¥1000)</span></th>
                <th class="px-3 py-3 text-center">全否認数<br><span class="font-normal text-xs">(¥500)</span></th>
                <th class="px-3 py-3 text-center">全否認数<br><span class="font-normal text-xs">(¥1000)</span></th>
                <th class="px-3 py-3 text-right">紹介報酬合計</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($activeUsers as $ru)
            @php
                $userApproved  = $reports->where('user_id', $ru->id);
                $userRejected  = $rejectedReports->where('user_id', $ru->id);
                $approved500   = $userApproved->filter(fn($r) => ($r->campaign?->referral_fee ?? 0) == 500)->count();
                $approved1000  = $userApproved->filter(fn($r) => ($r->campaign?->referral_fee ?? 0) == 1000)->count();
                $rejected500   = $userRejected->filter(fn($r) => ($r->campaign?->referral_fee ?? 0) == 500)->count();
                $rejected1000  = $userRejected->filter(fn($r) => ($r->campaign?->referral_fee ?? 0) == 1000)->count();
                $total         = $userApproved->sum(fn($r) => $r->campaign?->referral_fee ?? 0);
            @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-3 text-xs text-gray-500">{{ $ru->created_at?->format('Y/m/d') }}</td>
                <td class="px-3 py-3 font-mono text-xs text-pink-600">{{ $ru->referred_by_code }}</td>
                <td class="px-3 py-3 text-xs text-gray-600">{{ $ru->line_display_name ?? '-' }}</td>
                <td class="px-3 py-3 text-gray-800">{{ $ru->name ?? '-' }}</td>
                <td class="px-3 py-3 text-gray-600">{{ $ru->name_kana ?? '-' }}</td>
                <td class="px-3 py-3 text-center">{{ $approved500 ?: '-' }}</td>
                <td class="px-3 py-3 text-center">{{ $approved1000 ?: '-' }}</td>
                <td class="px-3 py-3 text-center {{ $rejected500 ? 'text-red-500' : 'text-gray-400' }}">{{ $rejected500 ?: '-' }}</td>
                <td class="px-3 py-3 text-center {{ $rejected1000 ? 'text-red-500' : 'text-gray-400' }}">{{ $rejected1000 ?: '-' }}</td>
                <td class="px-3 py-3 text-right font-bold text-green-600">¥{{ number_format($total) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="bg-gray-50">
            <tr>
                <td colspan="9" class="px-3 py-3 text-right font-bold text-gray-700">合計</td>
                <td class="px-3 py-3 text-right font-bold text-green-600">¥{{ number_format($expectedPay) }}</td>
            </tr>
        </tfoot>
    </table>
    </div>
    @else
    <div class="px-4 py-8 text-center text-gray-400">{{ $month->format('Y年n月') }}の承認済み報告はありません</div>
    @endif
</div>
@push('scripts')
<script>
function toggleRefCodes(btn, cls, count) {
    const extras = document.querySelectorAll('.' + cls);
    const isHidden = extras[0].classList.contains('hidden');
    extras.forEach(el => el.classList.toggle('hidden', !isHidden));
    btn.textContent = isHidden ? '折りたたむ' : '他' + count + '件を表示';
}
function copyUrl(url) {
    try {
        const el = document.createElement('textarea');
        el.value = url;
        el.style.cssText = 'position:fixed;top:0;left:0;opacity:0;pointer-events:none;';
        document.body.appendChild(el);
        el.focus();
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    } catch(e) {
        if (navigator.clipboard) navigator.clipboard.writeText(url).catch(() => {});
    }
    alert('コピーしました');
}
</script>
@endpush
@endsection
