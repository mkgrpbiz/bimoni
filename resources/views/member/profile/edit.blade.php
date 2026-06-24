@extends('layouts.member')
@section('title', 'プロフィール編集')
@section('content')
<div class="py-4">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('member.mypage') }}" class="text-gray-500 text-sm">← マイページ</a>
        <h1 class="text-lg font-bold text-gray-800">プロフィール編集</h1>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 rounded-xl px-4 py-3 text-sm mb-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('member.profile.update') }}" class="space-y-5">
        @csrf @method('PATCH')

        @foreach($fields as $field)
        @include('member._form_field', ['field' => $field, 'currentVal' => $responses->get($field->field_key, $user->{$field->maps_to} ?? null)])
        @endforeach

        {{-- 銀行口座 --}}
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 space-y-4">
            <h2 class="text-sm font-bold text-gray-700">銀行口座情報</h2>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">銀行名</label>
                    <input type="text" name="bank_name" value="{{ old('bank_name', $user->bank_name) }}"
                           placeholder="○○銀行"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">銀行コード</label>
                    <input type="text" name="bank_code" value="{{ old('bank_code', $user->bank_code) }}"
                           placeholder="0123" maxlength="4"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">支店名</label>
                    <input type="text" name="bank_branch_name" value="{{ old('bank_branch_name', $user->bank_branch_name) }}"
                           placeholder="○○支店"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">支店コード</label>
                    <input type="text" name="bank_branch_code" value="{{ old('bank_branch_code', $user->bank_branch_code) }}"
                           placeholder="012" maxlength="3"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">口座種別</label>
                    <select name="bank_account_type" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                        <option value="">選択</option>
                        <option value="普通" {{ old('bank_account_type', $user->bank_account_type) === '普通' ? 'selected' : '' }}>普通</option>
                        <option value="当座" {{ old('bank_account_type', $user->bank_account_type) === '当座' ? 'selected' : '' }}>当座</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">口座番号</label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $user->bank_account_number) }}"
                           placeholder="1234567" maxlength="8"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm @error('bank_account_number') border-red-400 @enderror">
                    @error('bank_account_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">口座名義（カナ）</label>
                <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $user->bank_account_name) }}"
                       placeholder="ヤマダ タロウ"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
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
@endsection
