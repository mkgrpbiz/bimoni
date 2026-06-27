@extends('layouts.member')

@section('title', 'ログイン')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] py-8">

    <div class="w-24 h-24 bg-pink-100 rounded-full flex items-center justify-center mb-6">
        <span class="text-4xl">💄</span>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-2">BIMONI</h1>
    <p class="text-gray-500 text-sm mb-8">美容モニターサービス</p>

    @if($devMode)
    {{-- 開発用ログイン --}}
    <div class="w-full bg-yellow-50 border border-yellow-300 rounded-lg p-4 mb-4">
        <p class="text-xs text-yellow-700 font-medium mb-1">⚙ 開発モード（LIFF未設定）</p>
        <p class="text-xs text-yellow-600">テスト用ログインを使用しています</p>
    </div>

    <div class="w-full space-y-4">
        @if($testUsers->isNotEmpty())
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h2 class="font-semibold text-gray-700 mb-3">既存ユーザーでログイン</h2>
            <form method="POST" action="{{ route('member.auth.dev') }}">
                @csrf
                <select name="user_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm mb-3">
                    @foreach($testUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->line_user_id }})</option>
                    @endforeach
                </select>
                <button type="submit" class="w-full bg-pink-500 text-white py-3 rounded-lg font-medium text-sm">
                    このユーザーでログイン
                </button>
            </form>
        </div>
        @endif

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h2 class="font-semibold text-gray-700 mb-3">新規テストユーザー作成</h2>
            <form method="POST" action="{{ route('member.auth.dev') }}">
                @csrf
                <input type="text" name="test_line_uid"
                       placeholder="テスト用LINE UID (空白で自動生成)"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm mb-3">
                <button type="submit" class="w-full bg-gray-700 text-white py-3 rounded-lg font-medium text-sm">
                    新規でログイン
                </button>
            </form>
        </div>
    </div>

    @else
    {{-- 本番LIFFログイン --}}
    <div class="w-full bg-white border border-gray-200 rounded-xl p-6 shadow-sm text-center">
        <p class="text-gray-500 text-sm mb-4">LINEアカウントでログインしてください</p>
        <div id="liff-loading" class="text-gray-400 text-sm">
            <div class="animate-spin w-8 h-8 border-2 border-pink-300 border-t-pink-500 rounded-full mx-auto mb-3"></div>
            LINE認証中...
        </div>
    </div>

    <script>
    liff.init({ liffId: '{{ config("services.line.liff_id") }}' })
        .then(() => {
            if (!liff.isLoggedIn()) {
                liff.login({ botPrompt: 'aggressive' });
                return;
            }
            return liff.getProfile();
        })
        .then(profile => {
            if (!profile) return;
            const params = new URLSearchParams(window.location.search);
            const referralCode = params.get('referral_code') || '';
            return fetch('{{ route("member.auth.liff") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    line_user_id: profile.userId,
                    line_display_name: profile.displayName,
                    referral_code: referralCode,
                }),
            });
        })
        .then(r => r ? r.json() : null)
        .then(data => {
            if (data && data.redirect) {
                window.location.href = data.redirect;
            }
        })
        .catch(err => {
            document.getElementById('liff-loading').innerHTML =
                '<p class="text-red-500">ログインに失敗しました。再度お試しください。</p>';
        });
    </script>
    @endif

</div>
@endsection
