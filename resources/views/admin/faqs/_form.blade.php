@php $isEdit = isset($faq) && $faq->exists; @endphp

<div class="bg-white rounded-lg shadow p-6 grid grid-cols-1 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">カテゴリ <span class="text-red-500">*</span></label>
        <input type="text" name="category" list="category-list"
               value="{{ old('category', $faq->category ?? request('category', '')) }}"
               class="w-full border rounded px-3 py-2 text-sm @error('category') border-red-400 @enderror"
               placeholder="例: 応募・実施・報告・解約・回収">
        <datalist id="category-list">
            @foreach($categories as $cat)
                <option value="{{ $cat }}">
            @endforeach
        </datalist>
        <p class="text-xs text-gray-400 mt-0.5">既存のカテゴリを選ぶか、新しいカテゴリ名を入力すると自動的にタブが増えます</p>
        @error('category')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">質問 <span class="text-red-500">*</span></label>
        <textarea name="question" rows="2"
                  class="w-full border rounded px-3 py-2 text-sm @error('question') border-red-400 @enderror">{{ old('question', $faq->question ?? '') }}</textarea>
        @error('question')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">回答 <span class="text-red-500">*</span></label>
        <textarea name="answer" rows="5"
                  class="w-full border rounded px-3 py-2 text-sm @error('answer') border-red-400 @enderror">{{ old('answer', $faq->answer ?? '') }}</textarea>
        @error('answer')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="flex justify-end gap-2 pt-2 border-t">
        <button type="submit" class="bg-pink-500 text-white px-6 py-2 rounded hover:bg-pink-600 text-sm">
            {{ $isEdit ? '更新する' : '登録する' }}
        </button>
    </div>
</div>
