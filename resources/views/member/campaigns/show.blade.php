@extends('layouts.member')

@section('title', $campaign->title)

@section('content')
<div class="py-2">

    {{-- 案件名（常に表示） --}}
    <h1 class="text-base font-bold text-gray-800 mb-4">{{ $campaign->title }}</h1>

    {{-- フォーム設定に従って案件情報 + 入力フィールドを表示 --}}
    @if($application)
        {{-- 応募済み：案件情報のみ表示 --}}
        <div class="space-y-4 mb-6">
            @foreach($appFields as $field)
                @if(str_starts_with($field->type, 'campaign_'))
                    @include('member._form_field', ['field' => $field, 'campaign' => $campaign])
                @endif
            @endforeach
        </div>
        <div class="w-full bg-gray-100 text-gray-500 py-4 rounded-xl text-center font-bold mb-2">
            応募済みです
        </div>
        <p class="text-xs text-gray-400 text-center">ステータス：{{ $application->status }}</p>

    @else
        {{-- 未応募：全フィールド + 応募フォーム --}}
        <form method="POST" action="{{ route('member.campaigns.apply', $campaign) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf

            @foreach($appFields as $field)
                @include('member._form_field', ['field' => $field, 'campaign' => $campaign])
            @endforeach

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
