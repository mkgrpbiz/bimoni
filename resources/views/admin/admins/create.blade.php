@extends('layouts.admin')

@section('title', '管理者追加')

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">管理者追加</h1>

    <form method="POST" action="{{ route('admin.admins.store') }}"
          class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">名前 <span class="text-red-400">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">メールアドレス <span class="text-red-400">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">役割 <span class="text-red-400">*</span></label>
            <div class="flex gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="role" value="admin"
                           {{ old('role', 'admin') === 'admin' ? 'checked' : '' }}
                           onchange="toggleMenuSelect(this.value)"
                           class="accent-pink-500">
                    <span class="text-sm dark:text-gray-200">管理者（全メニュー閲覧可）</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="role" value="operator"
                           {{ old('role') === 'operator' ? 'checked' : '' }}
                           onchange="toggleMenuSelect(this.value)"
                           class="accent-pink-500">
                    <span class="text-sm dark:text-gray-200">運用担当（メニュー選択）</span>
                </label>
            </div>
        </div>

        <div id="menuSelectArea" class="{{ old('role') === 'operator' ? '' : 'hidden' }}">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">閲覧可能メニュー</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 border dark:border-gray-600 rounded p-4">
                @foreach($menuKeys as $key => $label)
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="accessible_menus[]" value="{{ $key }}"
                           {{ in_array($key, old('accessible_menus', [])) ? 'checked' : '' }}
                           class="accent-pink-500">
                    <span class="text-sm dark:text-gray-200">{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">初期パスワードは <span class="font-mono font-bold">bimoni1234</span> です。</p>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-pink-500 text-white px-6 py-2 rounded hover:bg-pink-600 text-sm font-medium">追加</button>
            <a href="{{ route('admin.admins.index') }}" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 text-sm">キャンセル</a>
        </div>
    </form>
</div>

<script>
function toggleMenuSelect(role) {
    document.getElementById('menuSelectArea').classList.toggle('hidden', role !== 'operator');
}
</script>
@endsection
