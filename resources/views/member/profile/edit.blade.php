@extends('layouts.member')
@section('title', 'プロフィール編集')
@section('content')
<div class="py-4">
    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('member.mypage') }}" class="text-pink-500 text-sm">← マイページ</a>
    </div>
    <h1 class="font-bold text-gray-700 mb-3">プロフィール編集</h1>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 rounded-xl px-4 py-3 text-sm mb-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('member.profile.update') }}" class="space-y-5">
        @csrf @method('PATCH')

        {{-- 名前 --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                お名前 <span class="text-red-500 text-xs ml-1">必須</span>
            </label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                   placeholder="山田 太郎"
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error('name') border-red-400 @enderror">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- フリガナ --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                フリガナ <span class="text-red-500 text-xs ml-1">必須</span>
            </label>
            <input type="text" name="name_kana" value="{{ old('name_kana', $user->name_kana) }}"
                   placeholder="ヤマダ タロウ"
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error('name_kana') border-red-400 @enderror">
            @error('name_kana')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- 性別 --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                性別 <span class="text-red-500 text-xs ml-1">必須</span>
            </label>
            <div class="space-y-2">
                <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:border-pink-300">
                    <input type="radio" name="gender" value="female"
                           {{ old('gender', $user->gender) === 'female' ? 'checked' : '' }} class="accent-pink-500">
                    <span class="text-sm text-gray-700">女性</span>
                </label>
                <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:border-pink-300">
                    <input type="radio" name="gender" value="male"
                           {{ old('gender', $user->gender) === 'male' ? 'checked' : '' }} class="accent-pink-500">
                    <span class="text-sm text-gray-700">男性</span>
                </label>
            </div>
            @error('gender')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- 生年月日 --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                生年月日 <span class="text-red-500 text-xs ml-1">必須</span>
            </label>
            <input type="date" name="birthdate" value="{{ old('birthdate', $user->birthdate?->format('Y-m-d')) }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error('birthdate') border-red-400 @enderror">
            @error('birthdate')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- メールアドレス --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                メールアドレス <span class="text-red-500 text-xs ml-1">必須</span>
            </label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                   placeholder="example@email.com"
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error('email') border-red-400 @enderror">
            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- 銀行口座 --}}
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 space-y-4">
            <div>
                <h2 class="text-sm font-bold text-gray-700">銀行口座情報</h2>
                <p class="text-xs text-red-500 mt-0.5">※協力金支払いに必要な為、必須項目</p>
            </div>

            {{-- 銀行名 --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">銀行名 <span class="text-red-500 text-xs">必須</span></label>
                <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name', $user->bank_name) }}"
                       placeholder="銀行名を入力（例：みずほ）" autocomplete="off"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm @error('bank_name') border-red-400 @enderror">
                <input type="hidden" id="bank_code" name="bank_code" value="{{ old('bank_code', $user->bank_code) }}">
                @error('bank_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- 支店名 --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">支店名 <span class="text-red-500 text-xs">必須</span></label>
                <input type="text" id="bank_branch_name" name="bank_branch_name" value="{{ old('bank_branch_name', $user->bank_branch_name) }}"
                       placeholder="支店名を入力" autocomplete="off"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm @error('bank_branch_name') border-red-400 @enderror">
                <input type="hidden" id="bank_branch_code" name="bank_branch_code" value="{{ old('bank_branch_code', $user->bank_branch_code) }}">
                @error('bank_branch_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- 口座種別・口座番号 --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">口座種別 <span class="text-red-500 text-xs">必須</span></label>
                    <select name="bank_account_type" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                        <option value="">選択</option>
                        <option value="普通" {{ old('bank_account_type', $user->bank_account_type) === '普通' ? 'selected' : '' }}>普通</option>
                        <option value="当座" {{ old('bank_account_type', $user->bank_account_type) === '当座' ? 'selected' : '' }}>当座</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">口座番号 <span class="text-red-500 text-xs">必須</span></label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $user->bank_account_number) }}"
                           placeholder="1234567" maxlength="8" inputmode="numeric"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm @error('bank_account_number') border-red-400 @enderror">
                    @error('bank_account_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- 口座名義（一番下） --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">口座名義（カナ） <span class="text-red-500 text-xs">必須</span></label>
                <input type="text" name="bank_account_name" id="bank_account_name"
                       value="{{ old('bank_account_name', $user->bank_account_name) }}"
                       placeholder="ヤマダタロウ"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm @error('bank_account_name') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">スペースなし・カタカナで入力</p>
                @error('bank_account_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="pb-8">
            <button type="submit"
                    class="w-full bg-pink-500 text-white py-4 rounded-xl font-bold text-base shadow-md hover:bg-pink-600">
                更新する
            </button>
        </div>
    </form>
</div>

<script>
(function() {
    const nameEl = document.getElementById('bank_account_name');
    if (nameEl) {
        nameEl.addEventListener('blur', () => {
            nameEl.value = nameEl.value.replace(/\s+/g, '');
        });
    }
})();
</script>
@endsection
