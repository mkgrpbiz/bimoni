@extends('layouts.member')
@section('title', $campaign->title)
@section('content')
<div class="py-2">

    {{-- 案件名 --}}
    <h1 class="text-base font-bold text-gray-800 mb-4">{{ $campaign->title }}</h1>

    {{-- 案件画像 --}}
    @if($campaign->thumbnail)
    <div class="mb-4 rounded-xl overflow-hidden">
        <img src="{{ asset('storage/' . $campaign->thumbnail) }}"
             class="w-full object-contain max-h-72" alt="{{ $campaign->title }}">
    </div>
    @endif

    {{-- 案件情報 --}}
    <div class="space-y-3 mb-5">

        @if($campaign->description)
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <p class="text-xs font-bold text-gray-500 mb-2">案件案内説明</p>
            <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $campaign->description }}</div>
        </div>
        @endif

        {{-- 料金情報 --}}
        <div class="bg-white rounded-xl border border-gray-100 p-4 space-y-2">
            @if($campaign->initial_purchase_fee)
            <div class="flex justify-between items-center py-1 border-b border-gray-50">
                <span class="text-sm text-gray-600">初回購入費</span>
                <span class="font-bold text-gray-800">{{ number_format($campaign->initial_purchase_fee) }}円</span>
            </div>
            @endif
            <div class="flex justify-between items-center py-1 {{ $activeBonus ? 'border-b border-gray-50' : '' }}">
                <span class="text-sm text-gray-600">モニター協力金</span>
                <span class="font-bold text-pink-600">
                    @php
                        $coopDisplay = '';
                        if ($campaign->initial_purchase_fee && $campaign->cooperation_fee) {
                            $coopDisplay = number_format($campaign->initial_purchase_fee) . '+' . number_format($campaign->cooperation_fee) . '円';
                        } elseif ($campaign->initial_purchase_fee) {
                            $coopDisplay = number_format($campaign->initial_purchase_fee) . '円';
                        } elseif ($campaign->cooperation_fee) {
                            $coopDisplay = number_format($campaign->cooperation_fee) . '円';
                        } else {
                            $coopDisplay = '0円';
                        }
                    @endphp
                    {{ $coopDisplay }}
                </span>
            </div>
            @if($activeBonus)
            <div class="flex justify-between items-center py-1">
                <span class="text-sm text-gray-600">キャンペーン</span>
                <span class="font-bold text-red-500">+{{ number_format($activeBonus->bonus_amount) }}円</span>
            </div>
            @endif
        </div>
        @if($campaign->initial_purchase_fee)
        <p class="text-xs text-gray-400 -mt-1">※支払い方法などで多少前後する場合があります。</p>
        @endif

        @if($campaign->cancellation_info)
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <p class="text-xs font-bold text-gray-500 mb-2">解約について</p>
            <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $campaign->cancellation_info }}</div>
        </div>
        @endif

        @if($campaign->notes)
        <div class="bg-amber-50 rounded-xl border border-amber-100 p-4">
            <p class="text-xs font-bold text-amber-600 mb-2">注意事項</p>
            <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $campaign->notes }}</div>
        </div>
        @endif

    </div>

    @if($application)
        {{-- 応募済み --}}
        @if($campaign->collection_requirement === '回収必須')
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-600 font-medium mb-2">
            こちらの商品は継続分のみ回収必須となります。
        </div>
        @else
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-gray-600 font-medium mb-2">
            こちらの商品は継続分も回収必須ではありません。
        </div>
        @endif

        @if(in_array($application->status, ['pending', 'selected', 'line_contacted', 'scheduled', 'confirming']))
        <form method="POST" action="{{ route('member.campaigns.cancel', $campaign) }}"
              onsubmit="return confirm('応募を取り消しますか？')">
            @csrf
            <button type="submit" class="w-full bg-gray-100 text-gray-500 py-4 rounded-xl text-center font-bold mb-2">
                応募を取り消しする
            </button>
        </form>
        @else
        <div class="w-full bg-gray-100 text-gray-500 py-4 rounded-xl text-center font-bold mb-2">
            応募済みです
        </div>
        @endif
        <p class="text-xs text-gray-400 text-center mb-3">ステータス：{{ $application->getStatusLabel() }}</p>

    @elseif($campaign->status !== 'published')
        {{-- 募集終了 --}}
        <div class="w-full bg-gray-100 text-gray-500 py-4 rounded-xl text-center font-bold mb-2">
            この案件の募集は終了しました
        </div>

    @elseif($duplicateConflicts->isNotEmpty())
        {{-- 重複禁止商品に応募済み --}}
        <div class="w-full bg-gray-100 text-gray-500 py-4 rounded-xl text-center font-bold mb-2 px-4">
            こちらの商品は<br>{{ $duplicateConflicts->join('、') }}<br>と重複参加不可になります。
        </div>

    @else
        {{-- 応募フォーム --}}
        <form method="POST" action="{{ route('member.campaigns.apply', $campaign) }}"
              enctype="multipart/form-data" class="space-y-5">
            @csrf

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
                @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
            </div>
            @endif

            {{-- 購入可能時間 --}}
            <div>
                <p class="text-sm font-medium text-gray-700 mb-2">
                    購入が可能な時間をすべて選択してください <span class="text-red-500 text-xs">必須</span>
                </p>
                <p class="text-xs text-gray-500 mb-2">複数選択可</p>
                @php
                $timeOptions = ['いつでもOK', '10:00〜13:00', '14:00〜17:00', '18:00〜20:00', '21:00〜24:00'];
                $oldTimes = old('purchase_available_times', []);
                @endphp
                <div class="space-y-2">
                    @foreach($timeOptions as $time)
                    <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3 cursor-pointer">
                        <input type="checkbox" name="purchase_available_times[]" value="{{ $time }}"
                               {{ in_array($time, $oldTimes) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-pink-500">
                        <span class="text-sm text-gray-800">{{ $time }}</span>
                    </label>
                    @endforeach
                </div>
                @error('purchase_available_times')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- 継続希望（継続条件が2回前提/3回前提の場合は確認不要のため非表示） --}}
            @if(($campaign->continuation_cooperation_fee || $campaign->recurring_purchase_fee) && !in_array($campaign->continuation_condition, ['2回前提', '3回前提']))
            <div>
                <p class="text-sm font-medium text-gray-700 mb-2">
                    モニターで継続依頼がある場合、継続してモニター希望されますか？ <span class="text-red-500 text-xs">必須</span>
                </p>
                {{-- 継続費用情報 --}}
                <div class="bg-gray-50 rounded-xl border border-gray-100 p-3 mb-1 space-y-1.5">
                    @if($campaign->recurring_purchase_fee)
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">継続購入費</span>
                        <span class="text-gray-700 font-medium">{{ number_format($campaign->recurring_purchase_fee) }}円</span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-500">継続モニター協力金</span>
                        <span class="text-pink-600 font-bold">
                            @if($campaign->continuation_cooperation_fee)
                                @if($campaign->recurring_purchase_fee){{ number_format($campaign->recurring_purchase_fee) }}+@endif{{ number_format($campaign->continuation_cooperation_fee) }}円
                            @else
                                {{ number_format($campaign->recurring_purchase_fee ?? 0) }}円
                            @endif
                        </span>
                    </div>
                </div>
                @if($campaign->recurring_purchase_fee)<p class="text-xs text-gray-400 mb-3">※支払い方法などで多少前後する場合があります。</p>@endif
                <div class="grid grid-cols-2 gap-3">
                    @foreach(['希望' => '継続希望', '不可' => '継続不可'] as $val => $lbl)
                    <label class="flex items-center gap-2 border border-gray-200 rounded-xl px-4 py-3 cursor-pointer">
                        <input type="radio" name="continuation_wish" value="{{ $val }}" required
                               {{ old('continuation_wish') === $val ? 'checked' : '' }}
                               class="text-pink-500">
                        <span class="text-sm font-medium">{{ $lbl }}</span>
                    </label>
                    @endforeach
                </div>
                @error('continuation_wish')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            @endif

            @if($campaign->collection_requirement === '回収必須')
            <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-600 font-medium">
                こちらの商品は継続分のみ回収必須となります。
            </div>
            @else
            <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-gray-600 font-medium">
                こちらの商品は継続分も回収必須ではありません。
            </div>
            @endif

            <div class="pb-8">
                <button type="submit"
                        onclick="return confirm('この案件に応募しますか？')"
                        class="w-full bg-pink-500 text-white py-4 rounded-xl font-bold text-base shadow-md hover:bg-pink-600 active:bg-pink-700">
                    この案件に応募する
                </button>
            </div>
        </form>
    @endif

    <a href="{{ route('member.campaigns.index') }}"
       class="block text-center text-sm text-gray-400 mt-2 mb-8">
        ← 案件一覧に戻る
    </a>
</div>
@endsection
