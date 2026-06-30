@extends('layouts.member')
@section('title', 'アカウント引き継ぎ')
@section('content')
<div class="py-4">
    <h1 class="text-xl font-bold text-gray-800 mb-1">アカウント引き継ぎ</h1>
    <p class="text-sm text-gray-500 mb-6">以前ご登録済みの方は、こちらから既存データを引き継いでください。</p>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if($candidates->isNotEmpty())
    {{-- 複数候補 --}}
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-3 mb-5 text-sm text-yellow-800">
        複数の候補が見つかりました。ご自身のデータを選択してください。
    </div>
    <div class="space-y-3 mb-6">
        @foreach($candidates as $c)
        <form method="POST" action="{{ route('member.transfer.link', $c->id) }}">
            @csrf
            <button type="submit" class="w-full text-left bg-white border border-gray-200 rounded-lg px-4 py-3 hover:border-pink-400 hover:bg-pink-50 transition">
                <p class="font-medium text-gray-800">{{ $c->name }}（{{ $c->name_kana }}）</p>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $c->birthdate ? \Carbon\Carbon::parse($c->birthdate)->format('Y年n月j日') : '' }}
                    {{ $c->email ? '／ ' . $c->email : '' }}
                </p>
            </button>
        </form>
        @endforeach
    </div>
    <a href="{{ route('member.transfer') }}" class="text-sm text-gray-400 hover:underline">← 検索に戻る</a>

    @else
    {{-- 検索フォーム --}}
    <form method="POST" action="{{ route('member.transfer.search') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">お名前 <span class="text-red-500 text-xs ml-1">必須</span></label>
            <input type="text" name="name" value="{{ old('name') }}" placeholder="山田 太郎"
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error('name') border-red-400 @enderror">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">フリガナ <span class="text-red-500 text-xs ml-1">必須</span></label>
            <input type="text" name="name_kana" value="{{ old('name_kana') }}" placeholder="ヤマダ タロウ"
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error('name_kana') border-red-400 @enderror">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">生年月日</label>
            <input type="date" name="birthdate" value="{{ old('birthdate') }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="example@email.com"
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm">
        </div>
        <p class="text-xs text-gray-400">生年月日またはメールアドレスのどちらか一方を入力してください。</p>
        <button type="submit"
                class="w-full bg-pink-500 text-white font-bold py-3 rounded-lg hover:bg-pink-600 transition">
            引き継ぎデータを検索
        </button>
    </form>
    <div class="mt-6 text-center">
        <a href="{{ route('member.register') }}" class="text-sm text-gray-400 hover:underline">新規登録はこちら</a>
    </div>
    @endif
</div>
@endsection
