@extends('layouts.member')

@section('title', '会員登録')

@section('content')
<div class="py-4">
    <h1 class="text-xl font-bold text-gray-800 mb-1">会員情報の登録</h1>
    <p class="text-sm text-gray-500 mb-6">以下の情報をご入力ください。</p>

    <form method="POST" action="{{ route('member.register.store') }}" class="space-y-5">
        @csrf

        @foreach($fields as $field)
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                {{ $field->label }}
                @if($field->is_required)
                    <span class="text-red-500 text-xs ml-1">必須</span>
                @else
                    <span class="text-gray-400 text-xs ml-1">任意</span>
                @endif
            </label>

            @php $inputName = 'field_' . $field->field_key; @endphp

            @if($field->type === 'text')
                <input type="text" name="{{ $inputName }}"
                       value="{{ old($inputName) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error($inputName) border-red-400 @enderror">

            @elseif($field->type === 'textarea')
                <textarea name="{{ $inputName }}" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error($inputName) border-red-400 @enderror">{{ old($inputName) }}</textarea>

            @elseif($field->type === 'date')
                <input type="date" name="{{ $inputName }}"
                       value="{{ old($inputName) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error($inputName) border-red-400 @enderror">

            @elseif($field->type === 'tel')
                <input type="tel" name="{{ $inputName }}"
                       value="{{ old($inputName) }}"
                       placeholder="090-0000-0000"
                       class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error($inputName) border-red-400 @enderror">

            @elseif($field->type === 'email')
                <input type="email" name="{{ $inputName }}"
                       value="{{ old($inputName) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error($inputName) border-red-400 @enderror">

            @elseif($field->type === 'number')
                <input type="number" name="{{ $inputName }}"
                       value="{{ old($inputName) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error($inputName) border-red-400 @enderror">

            @elseif($field->type === 'radio' && $field->options)
                <div class="space-y-2">
                    @foreach($field->options as $option)
                    <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:border-pink-300">
                        <input type="radio"
                               name="{{ $inputName }}"
                               value="{{ $option['value'] }}"
                               {{ old($inputName) == $option['value'] ? 'checked' : '' }}
                               class="accent-pink-500">
                        <span class="text-sm text-gray-700">{{ $option['label'] }}</span>
                    </label>
                    @endforeach
                </div>

            @elseif($field->type === 'checkbox' && $field->options)
                <div class="space-y-2">
                    @foreach($field->options as $option)
                    <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:border-pink-300">
                        <input type="checkbox"
                               name="{{ $inputName }}[]"
                               value="{{ $option['value'] }}"
                               {{ in_array($option['value'], old($inputName, []) ?: []) ? 'checked' : '' }}
                               class="accent-pink-500">
                        <span class="text-sm text-gray-700">{{ $option['label'] }}</span>
                    </label>
                    @endforeach
                </div>

            @elseif($field->type === 'select' && $field->options)
                <select name="{{ $inputName }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error($inputName) border-red-400 @enderror">
                    <option value="">選択してください</option>
                    @foreach($field->options as $option)
                        <option value="{{ $option['value'] }}" {{ old($inputName) == $option['value'] ? 'selected' : '' }}>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </select>
            @endif

            @error($inputName)
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        @endforeach

        <div class="pt-4 pb-8">
            <button type="submit"
                    class="w-full bg-pink-500 text-white py-4 rounded-xl font-bold text-base shadow-md hover:bg-pink-600 active:bg-pink-700">
                登録して案件を見る
            </button>
        </div>
    </form>
</div>
@endsection
