@php
    $pickerId   = $pickerId ?? 'img_' . uniqid();
    $inputName  = $inputName ?? 'image';
    $labelText  = $labelText ?? '画像';
    $required   = $required ?? false;
@endphp
<div x-data="imagePicker_{{ $pickerId }}()" class="space-y-2">
    <label class="block text-sm font-medium text-gray-700">
        {{ $labelText }}
        <span class="{{ $required ? 'text-red-500' : 'text-gray-400' }} text-xs ml-1">{{ $required ? '必須' : '任意' }}</span>
    </label>
    @if($description ?? false)
        <p class="text-xs text-gray-500">{{ $description }}</p>
    @endif

    {{-- 選択前: タップエリア --}}
    <label x-show="!preview"
           class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-pink-400 hover:bg-pink-50 transition-colors">
        <span class="text-3xl mb-1">📷</span>
        <span class="text-sm text-gray-500">タップして画像を選択</span>
        <span class="text-xs text-gray-400 mt-0.5">JPG・PNG・WEBP・最大10MB</span>
        <input type="file" name="{{ $inputName }}" accept="image/*" class="hidden"
               @change="onSelect($event)">
    </label>

    {{-- 選択後: プレビュー --}}
    <div x-show="preview" x-cloak class="relative">
        <img :src="preview" class="w-full max-h-64 object-cover rounded-xl border border-gray-200">
        <div class="mt-2 flex items-center justify-between">
            <p class="text-xs text-gray-500 truncate flex-1" x-text="fileName"></p>
            <button type="button"
                    @click="reset()"
                    class="text-xs text-red-500 hover:underline ml-3 shrink-0">
                取り消す
            </button>
        </div>
    </div>

    @error($inputName)
        <p class="text-red-500 text-xs">{{ $message }}</p>
    @enderror
</div>

<script>
function imagePicker_{{ $pickerId }}() {
    return {
        preview: null,
        fileName: '',
        fileInput: null,
        onSelect(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.fileInput = e.target;
            this.fileName  = file.name;
            const reader = new FileReader();
            reader.onload = ev => { this.preview = ev.target.result; };
            reader.readAsDataURL(file);
        },
        reset() {
            this.preview  = null;
            this.fileName = '';
            if (this.fileInput) {
                this.fileInput.value = '';
            }
        }
    };
}
</script>
