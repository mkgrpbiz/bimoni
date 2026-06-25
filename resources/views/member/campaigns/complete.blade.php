@extends('layouts.member')
@section('title', '応募完了')
@section('content')
<div class="py-6 px-2">

    <div class="text-center mb-8">
        <div class="text-5xl mb-4">✅</div>
        <h1 class="text-2xl font-bold text-gray-800">応募完了しました。</h1>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 p-5 mb-4 text-sm text-gray-700 leading-relaxed space-y-2">
        <p>順に案内していますので、案内まで時間がかかる場合があります。</p>
        <p class="text-gray-500 text-xs">※途中で終了する場合もあり、応募=確定ではございません。</p>
    </div>

    <div class="bg-pink-50 rounded-xl border border-pink-100 p-5 mb-4">
        <p class="text-sm font-bold text-pink-600 mb-2">Instagramフォローのお願い</p>
        <p class="text-sm text-gray-700 mb-3 leading-relaxed">
            また、下のリンクからモニター実施時専用インスタグラムアカウントのフォローをお願いします。
        </p>
        <a href="https://bimoni.online/insta/pre_follow/"
           target="_blank"
           class="block w-full bg-gradient-to-r from-pink-500 to-purple-500 text-white text-center py-3 rounded-xl font-bold text-sm">
            Instagramをフォローする
        </a>
        <p class="text-xs text-gray-400 mt-2 text-center">※こちらのアカウントをフォローしていないとモニターを実施することができません。</p>
    </div>

    <div class="bg-amber-50 rounded-xl border border-amber-100 p-5 mb-6">
        <p class="text-xs font-bold text-amber-600 mb-2">【注意事項】</p>
        <ul class="text-sm text-gray-700 space-y-1">
            <li>・モニター対象時間外の購入は協力金対象外です。</li>
            <li>・案内があるまで購入しないでください。</li>
        </ul>
    </div>

    <a href="{{ route('member.mypage') }}"
       class="block w-full bg-pink-500 text-white text-center py-4 rounded-xl font-bold text-base">
        マイページへ
    </a>
</div>
@endsection
