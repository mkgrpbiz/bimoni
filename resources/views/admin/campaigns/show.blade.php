@extends('layouts.admin')

@section('title', $campaign->title)

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.campaigns.index') }}"
       class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">← 一覧に戻る</a>
    <h1 class="text-2xl font-bold text-gray-800">{{ $campaign->title }}</h1>
    <a href="{{ route('admin.cancellation_settings.edit', $campaign) }}"
       class="ml-auto bg-white border border-pink-300 text-pink-600 px-4 py-1.5 rounded hover:bg-pink-50 text-sm">解約方法管理</a>
    <a href="{{ route('admin.campaigns.edit', $campaign) }}"
       class="bg-pink-500 text-white px-4 py-1.5 rounded hover:bg-pink-600 text-sm">編集</a>
</div>

<div class="bg-white rounded-lg shadow p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
    <div><span class="text-gray-700">種別：</span>{{ $campaign->getTypeLabel() }}</div>
    <div><span class="text-gray-700">ステータス：</span>{{ $campaign->getStatusLabel() }}</div>
    <div><span class="text-gray-700">カテゴリ：</span>{{ $campaign->category?->name ?? '未設定' }}</div>
    <div><span class="text-gray-700">PR媒体：</span>{{ $campaign->pr_media ?? '未設定' }}</div>
    <div>
        <span class="text-gray-700">モニター協力金：</span>
        @if($campaign->cooperation_fee_formula)
            {{ $campaign->cooperation_fee_formula }} = ¥{{ number_format($campaign->cooperation_fee) }}
        @else
            ¥{{ number_format($campaign->cooperation_fee) }}
        @endif
    </div>
    <div>
        <span class="text-gray-700">継続モニター協力金：</span>
        @if($campaign->continuation_cooperation_fee_formula)
            @php $formula = preg_replace('/^0\+/', '', $campaign->continuation_cooperation_fee_formula); @endphp
            {{ $formula }} = ¥{{ number_format($campaign->continuation_cooperation_fee) }}
        @elseif($campaign->continuation_cooperation_fee)
            ¥{{ number_format($campaign->continuation_cooperation_fee) }}
        @else
            -
        @endif
    </div>
    <div><span class="text-gray-700">回収前提（継続）：</span>{{ $campaign->collection_requirement ?? '-' }}</div>
    <div><span class="text-gray-700">回収個数判定（継続）：</span>{{ $campaign->collection_count_judgment ? $campaign->collection_count_judgment . '個' : '-' }}</div>
    <div><span class="text-gray-700">募集人数：</span>{{ $campaign->capacity }}名</div>
    <div><span class="text-gray-700">打診予定数：</span>{{ $campaign->solicitation_target ?? '-' }}名</div>
    <div><span class="text-gray-700">粗利：</span>{{ $campaign->gross_profit !== null ? '¥'.number_format($campaign->gross_profit) : '-' }}</div>
    <div><span class="text-gray-700">募集期間：</span>
        {{ $campaign->application_start_at?->format('Y/m/d') ?? '-' }} 〜
        {{ $campaign->application_end_at?->format('Y/m/d') ?? '-' }}
    </div>
    @if($campaign->tags->count())
    <div class="md:col-span-2">
        <span class="text-gray-700">タグ：</span>
        @foreach($campaign->tags as $tag)
            <span class="bg-gray-500 text-white px-2 py-0.5 rounded text-xs mr-1">{{ $tag->name }}</span>
        @endforeach
    </div>
    @endif
    @if($campaign->description)
    <div class="md:col-span-2">
        <p class="text-gray-700 mb-1">案件内容：</p>
        <p class="whitespace-pre-wrap">{{ $campaign->description }}</p>
    </div>
    @endif
    @if($campaign->notes)
    <div class="md:col-span-2">
        <p class="text-gray-700 mb-1">注意事項：</p>
        <p class="whitespace-pre-wrap">{{ $campaign->notes }}</p>
    </div>
    @endif
</div>
@endsection

