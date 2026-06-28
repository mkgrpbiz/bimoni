@extends('layouts.admin')

@section('title', '協力金管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">協力金管理</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    @foreach($blocks as $i => $block)
    <div class="bg-white rounded-xl shadow p-6 {{ $i === 1 ? 'border-l-4 border-pink-400' : '' }}">
        <p class="text-xs text-gray-400 mb-1">{{ $i === 0 ? '先月' : '当月' }}</p>
        <h2 class="text-lg font-bold text-gray-700 mb-4">{{ $block['month']->format('Y年n月') }}</h2>

        <div class="mb-4">
            <p class="text-xs text-gray-400 mb-1">協力金合計</p>
            <p class="text-3xl font-bold {{ $i === 1 ? 'text-pink-600' : 'text-gray-700' }}">
                ¥{{ number_format($block['total']) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">{{ $block['count'] }}件</p>
        </div>

        <div class="mb-5">
            <p class="text-xs text-gray-400 mb-1">ステータス</p>
            @if($block['status'] === 'pending')
                <span class="inline-flex items-center gap-1.5 bg-yellow-100 text-yellow-700 text-sm font-medium px-3 py-1 rounded-full">
                    <span class="w-2 h-2 rounded-full bg-yellow-400"></span>予約待ち
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 bg-green-100 text-green-700 text-sm font-medium px-3 py-1 rounded-full">
                    <span class="w-2 h-2 rounded-full bg-green-400"></span>予約済
                </span>
            @endif
        </div>

        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.points.csv', ['month' => $block['month']->format('Y-m')]) }}"
               class="bg-gray-500 text-white px-4 py-2 rounded text-sm hover:bg-gray-600">CSV出力</a>

            @if($block['hasPending'] && $block['total'] > 0)
            <form method="POST" action="{{ route('admin.points.mark_reserved') }}"
                  onsubmit="return confirm('{{ $block['month']->format('Y年n月') }}の協力金をすべて予約済にしますか？')">
                @csrf @method('PATCH')
                <input type="hidden" name="month" value="{{ $block['month']->format('Y-m') }}">
                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded text-sm hover:bg-green-600">
                    → 予約済にする
                </button>
            </form>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endsection
