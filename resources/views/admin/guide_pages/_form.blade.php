@php $isEdit = isset($page) && $page->exists; @endphp

<div class="bg-white rounded-lg shadow p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">タイトル <span class="text-red-500">*</span></label>
        <input type="text" name="title" value="{{ old('title', $page->title ?? '') }}"
               class="w-full border rounded px-3 py-2 text-sm @error('title') border-red-400 @enderror">
        @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">スラッグ（URL用の半角英数） <span class="text-red-500">*</span></label>
        <input type="text" name="slug" value="{{ old('slug', $page->slug ?? '') }}"
               class="w-full border rounded px-3 py-2 text-sm @error('slug') border-red-400 @enderror"
               placeholder="beginner-guide">
        <p class="text-xs text-gray-400 mt-0.5">会員向けURL: {{ url('/member/guide/') }}/<span class="font-mono">スラッグ</span></p>
        @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">ヒーロー画像</label>
        @if($isEdit && $page->hero_image)
        <div class="mb-2">
            <img src="{{ asset('storage/' . $page->hero_image) }}" alt="現在の画像" class="w-40 rounded border">
        </div>
        @endif
        <input type="file" name="hero_image" accept="image/*" class="w-full border rounded px-3 py-2 text-sm @error('hero_image') border-red-400 @enderror">
        @error('hero_image')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">CTAボタン文言</label>
        <input type="text" name="cta_label" value="{{ old('cta_label', $page->cta_label ?? '') }}"
               class="w-full border rounded px-3 py-2 text-sm @error('cta_label') border-red-400 @enderror"
               placeholder="回収依頼はマイページからお願いします。">
        @error('cta_label')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">CTAボタンリンク先</label>
        <input type="text" name="cta_url" value="{{ old('cta_url', $page->cta_url ?? '') }}"
               class="w-full border rounded px-3 py-2 text-sm @error('cta_url') border-red-400 @enderror"
               placeholder="https://...">
        <p class="text-xs text-gray-400 mt-0.5">文言・リンク先どちらも空欄ならボタンは表示されません</p>
        @error('cta_url')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2 flex justify-end gap-2 pt-2 border-t">
        <button type="submit" class="bg-pink-500 text-white px-6 py-2 rounded hover:bg-pink-600 text-sm">
            {{ $isEdit ? '更新する' : '登録する' }}
        </button>
    </div>
</div>
