@extends('layouts.admin')

@section('title', 'ユーザー詳細')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.users.index') }}"
       class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">← ユーザー一覧</a>
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">ユーザー詳細</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    {{-- プロフィール --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">プロフィール</h2>
        <dl class="text-sm space-y-1.5">
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">ユーザーID</dt>
                <dd class="font-mono text-gray-800 dark:text-gray-200">{{ $user->bimoni_user_id ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">登録コード</dt>
                <dd class="font-mono text-gray-800 dark:text-gray-200">{{ $user->referred_by_code ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">氏名</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">{{ $user->name ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">フリガナ</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $user->name_kana ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">性別</dt>
                <dd class="text-gray-800 dark:text-gray-200">
                    {{ match($user->gender ?? '') { 'male' => '男性', 'female' => '女性', 'other' => 'その他', default => '-' } }}
                </dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">生年月日</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $user->birthdate?->format('Y/m/d') ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">エリア</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $user->area ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">登録日</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $user->created_at?->format('Y/m/d') ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    {{-- 銀行口座（内部） --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">銀行口座情報</h2>
        <dl class="text-sm space-y-1.5">
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">銀行名</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $user->bank_name ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">銀行コード</dt>
                <dd class="font-mono text-gray-800 dark:text-gray-200">{{ $user->bank_code ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">支店名</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $user->bank_branch_name ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">支店コード</dt>
                <dd class="font-mono text-gray-800 dark:text-gray-200">{{ $user->bank_branch_code ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">口座種別</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $user->bank_account_type ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">口座番号</dt>
                <dd class="font-mono text-gray-800 dark:text-gray-200">{{ $user->bank_account_number ?? '-' }}</dd>
            </div>
            <div class="flex gap-2">
                <dt class="text-gray-700 dark:text-gray-400 w-28 shrink-0">口座名義</dt>
                <dd class="text-gray-800 dark:text-gray-200">{{ $user->bank_account_name ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    {{-- 統計 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-3">実績サマリー</h2>
        @php
            $approvedReports = $reports->where('status', 'approved');
            $pendingPay = $approvedReports->where('payment_status', 'pending')->sum(fn($r) => $r->campaign?->cooperation_fee ?? 0);
            $paidTotal  = $approvedReports->where('payment_status', 'paid')->sum(fn($r) => $r->campaign?->cooperation_fee ?? 0);
        @endphp
        <dl class="text-sm space-y-2">
            <div class="flex justify-between">
                <dt class="text-gray-700 dark:text-gray-400">総応募数</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">{{ $applications->count() }} 件</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-700 dark:text-gray-400">モニター実施数</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">{{ $approvedReports->count() }} 件</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-700 dark:text-gray-400">支払待ち合計</dt>
                <dd class="font-medium text-yellow-600 dark:text-yellow-400">¥{{ number_format($pendingPay) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-700 dark:text-gray-400">累計支払い金額</dt>
                <dd class="font-medium text-green-600 dark:text-green-400">¥{{ number_format($paidTotal) }}</dd>
            </div>
        </dl>
    </div>
</div>

{{-- モニター実施履歴 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-6">
    <div class="px-5 py-3 border-b dark:border-gray-700">
        <h2 class="font-bold text-gray-700 dark:text-gray-200">モニター実施履歴</h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">報告日</th>
                <th class="px-4 py-3 text-left">モニター名</th>
                <th class="px-4 py-3 text-left">報告ステータス</th>
                <th class="px-4 py-3 text-left">支払いステータス</th>
                <th class="px-4 py-3 text-right">協力金</th>
                <th class="px-4 py-3 text-left">支払日</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($reports as $report)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-400 whitespace-nowrap">
                    {{ $report->created_at->format('Y/m/d') }}
                </td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ $report->campaign?->title ?? '-' }}</td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded {{ $report->getStatusColor() }}">
                        {{ $report->getStatusLabel() }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    @if($report->status === 'approved')
                    <span class="text-xs px-2 py-0.5 rounded
                        {{ $report->payment_status === 'paid' ? 'bg-green-500 text-white' : 'bg-yellow-500 text-white' }}">
                        {{ $report->payment_status === 'paid' ? '支払済' : '支払待ち' }}
                    </span>
                    @else
                    <span class="text-xs text-gray-700 dark:text-gray-500">-</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-gray-200">
                    @if($report->status === 'approved')
                        ¥{{ number_format($report->campaign?->cooperation_fee ?? 0) }}
                    @else
                        -
                    @endif
                </td>
                <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-400">
                    {{ $report->paid_at?->format('Y/m/d') ?? '-' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-700 dark:text-gray-500">実施履歴がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- 応募履歴 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
    <div class="px-5 py-3 border-b dark:border-gray-700">
        <h2 class="font-bold text-gray-700 dark:text-gray-200">応募履歴</h2>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">応募日</th>
                <th class="px-4 py-3 text-left">モニター名</th>
                <th class="px-4 py-3 text-left">ステータス</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-gray-700">
            @forelse($applications as $app)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-400">{{ $app->applied_at->format('Y/m/d') }}</td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200">{{ $app->campaign?->title ?? '-' }}</td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded {{ $app->getStatusColor() }}">
                        {{ $app->getStatusLabel() }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="px-4 py-8 text-center text-gray-700 dark:text-gray-500">応募履歴がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
