@extends('layouts.member')
@section('title', '会員登録')
@section('content')
<div class="py-4">
    <h1 class="text-xl font-bold text-gray-800 mb-1">会員情報の登録</h1>
    <p class="text-sm text-gray-500 mb-6">以下の情報をご入力ください。</p>

    <form method="POST" action="{{ route('member.register.store') }}" class="space-y-5">
        @csrf

        {{-- 紹介コード --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                紹介コード <span class="text-gray-400 text-xs">任意</span>
            </label>
            <input type="text" name="referred_by_code" value="{{ old('referred_by_code') }}"
                   placeholder="例: ABC123"
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error('referred_by_code') border-red-400 @enderror">
            @error('referred_by_code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- 名前 --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                お名前 <span class="text-red-500 text-xs ml-1">必須</span>
            </label>
            <input type="text" name="name" value="{{ old('name') }}"
                   placeholder="山田 太郎"
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error('name') border-red-400 @enderror">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- ふりがな --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                ふりがな <span class="text-red-500 text-xs ml-1">必須</span>
            </label>
            <input type="text" name="name_kana" value="{{ old('name_kana') }}"
                   placeholder="やまだ たろう"
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error('name_kana') border-red-400 @enderror">
            @error('name_kana')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- 性別 --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                性別 <span class="text-red-500 text-xs ml-1">必須</span>
            </label>
            <div class="space-y-2">
                <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:border-pink-300 @error('gender') border-red-400 @enderror">
                    <input type="radio" name="gender" value="female"
                           {{ old('gender') === 'female' ? 'checked' : '' }} class="accent-pink-500">
                    <span class="text-sm text-gray-700">女性</span>
                </label>
                <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:border-pink-300 @error('gender') border-red-400 @enderror">
                    <input type="radio" name="gender" value="male"
                           {{ old('gender') === 'male' ? 'checked' : '' }} class="accent-pink-500">
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
            <input type="date" name="birthdate" value="{{ old('birthdate') }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error('birthdate') border-red-400 @enderror">
            @error('birthdate')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- メールアドレス --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                メールアドレス <span class="text-gray-400 text-xs">任意</span>
            </label>
            <input type="email" name="email" value="{{ old('email') }}"
                   placeholder="example@email.com"
                   class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error('email') border-red-400 @enderror">
            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- 銀行口座 --}}
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 space-y-4">
            <h2 class="text-sm font-bold text-gray-700">銀行口座情報（協力金の振込先）</h2>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">銀行名 <span class="text-gray-400 text-xs">任意</span></label>
                    <input type="text" name="bank_name" value="{{ old('bank_name') }}"
                           placeholder="○○銀行"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">銀行コード</label>
                    <input type="text" name="bank_code" value="{{ old('bank_code') }}"
                           placeholder="0123" maxlength="4"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">支店名</label>
                    <input type="text" name="bank_branch_name" value="{{ old('bank_branch_name') }}"
                           placeholder="○○支店"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">支店コード</label>
                    <input type="text" name="bank_branch_code" value="{{ old('bank_branch_code') }}"
                           placeholder="012" maxlength="3"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">口座種別</label>
                    <select name="bank_account_type" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                        <option value="">選択</option>
                        <option value="普通" {{ old('bank_account_type') === '普通' ? 'selected' : '' }}>普通</option>
                        <option value="当座" {{ old('bank_account_type') === '当座' ? 'selected' : '' }}>当座</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">口座番号</label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}"
                           placeholder="1234567" maxlength="8"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm @error('bank_account_number') border-red-400 @enderror">
                    @error('bank_account_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">口座名義（カナ）</label>
                <input type="text" name="bank_account_name" value="{{ old('bank_account_name') }}"
                       placeholder="ヤマダ タロウ"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
            </div>
        </div>

        {{-- 利用規約・プライバシーポリシー --}}
        @if($terms->content || $privacy->content)
        <div class="space-y-4">
            @if($terms->content)
            <div>
                <p class="text-sm font-bold text-gray-700 mb-2">{{ $terms->title }}</p>
                <div class="bg-gray-50 border rounded-lg p-3 h-40 overflow-y-auto text-xs text-gray-600 leading-relaxed whitespace-pre-wrap">{{ $terms->content }}</div>
            </div>
            @endif
            @if($privacy->content)
            <div>
                <p class="text-sm font-bold text-gray-700 mb-2">{{ $privacy->title }}</p>
                <div class="bg-gray-50 border rounded-lg p-3 h-40 overflow-y-auto text-xs text-gray-600 leading-relaxed whitespace-pre-wrap">{{ $privacy->content }}</div>
            </div>
            @endif
        </div>
        @endif

        <div class="bg-pink-50 border border-pink-200 rounded-xl p-4">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="agree_terms" value="1"
                       class="accent-pink-500 mt-0.5 w-5 h-5 shrink-0">
                <span class="text-sm text-gray-700">
                    利用規約およびプライバシーポリシーに同意します
                </span>
            </label>
            @error('agree_terms')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
        </div>

        <div class="pt-2 pb-8">
            <button type="submit"
                    class="w-full bg-pink-500 text-white py-4 rounded-xl font-bold text-base shadow-md hover:bg-pink-600 active:bg-pink-700">
                登録して案件を見る
            </button>
        </div>
    </form>
</div>
@endsection
