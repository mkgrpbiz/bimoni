@extends('layouts.admin')

@section('title', 'フォーム項目管理')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">会員登録フォーム項目管理</h1>

@if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-4 py-2 rounded mb-4 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- 項目一覧 --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left">順</th>
                        <th class="px-4 py-3 text-left">ラベル / キー</th>
                        <th class="px-4 py-3 text-left">種別</th>
                        <th class="px-4 py-3 text-center">必須</th>
                        <th class="px-4 py-3 text-center">表示</th>
                        <th class="px-4 py-3 text-left">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @foreach($fields as $field)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-center">{{ $field->sort_order }}</td>
                        <td class="px-4 py-3">
                            <span class="font-medium dark:text-gray-200">{{ $field->label }}</span>
                            <span class="text-xs text-gray-400 ml-1 font-mono">{{ $field->field_key }}</span>
                            @if($field->is_system)
                                <span class="ml-1 text-xs bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 px-1.5 rounded">システム</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $field->type }}</td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" action="{{ route('admin.form_fields.toggle', $field) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="field" value="is_required">
                                <button type="submit"
                                        class="text-xs px-2 py-0.5 rounded {{ $field->is_required ? 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-300' : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' }}">
                                    {{ $field->is_required ? '必須' : '任意' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" action="{{ route('admin.form_fields.toggle', $field) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="field" value="is_visible">
                                <button type="submit"
                                        class="text-xs px-2 py-0.5 rounded {{ $field->is_visible ? 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' }}">
                                    {{ $field->is_visible ? '表示' : '非表示' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                {{-- 並び順変更 --}}
                                <form method="POST" action="{{ route('admin.form_fields.update', $field) }}" class="flex items-center gap-1">
                                    @csrf @method('PATCH')
                                    <input type="number" name="sort_order" value="{{ $field->sort_order }}"
                                           class="w-14 border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-1 py-0.5 text-xs text-center">
                                    <input type="hidden" name="label" value="{{ $field->label }}">
                                    <input type="hidden" name="is_required" value="{{ $field->is_required ? '1' : '0' }}">
                                    <input type="hidden" name="is_visible" value="{{ $field->is_visible ? '1' : '0' }}">
                                    <button type="submit" class="text-xs text-blue-500 hover:underline">並替</button>
                                </form>

                                @if(!$field->is_system)
                                <form method="POST" action="{{ route('admin.form_fields.destroy', $field) }}"
                                      onsubmit="return confirm('削除しますか？')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-400 hover:underline">削除</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- 新規追加フォーム --}}
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-4">項目を追加</h2>
            <form method="POST" action="{{ route('admin.form_fields.store') }}" class="space-y-3">
                @csrf

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ラベル（表示名）<span class="text-red-400">*</span></label>
                    <input type="text" name="label" required value="{{ old('label') }}"
                           class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
                    @error('label')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">フィールドキー（英数字_）<span class="text-red-400">*</span></label>
                    <input type="text" name="field_key" required value="{{ old('field_key') }}"
                           placeholder="例: instagram_url"
                           class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm font-mono">
                    @error('field_key')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">入力形式<span class="text-red-400">*</span></label>
                    <select name="type" required
                            class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
                        <option value="text">テキスト</option>
                        <option value="textarea">テキストエリア</option>
                        <option value="date">日付</option>
                        <option value="radio">単一選択（ラジオ）</option>
                        <option value="checkbox">複数選択（チェックボックス）</option>
                        <option value="select">ドロップダウン</option>
                        <option value="tel">電話番号</option>
                        <option value="email">メールアドレス</option>
                        <option value="number">数値</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                        選択肢（ラジオ/チェック/セレクト用）
                    </label>
                    <textarea name="options" rows="4"
                              class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm font-mono"
                              placeholder="1行に1つ入力&#10;例:&#10;オプションA&#10;オプションB">{{ old('options') }}</textarea>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">1行1選択肢で入力</p>
                </div>

                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="is_required" value="1" {{ old('is_required') ? 'checked' : '' }} class="accent-pink-500">
                        必須
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="is_visible" value="1" {{ old('is_visible', true) ? 'checked' : '' }} class="accent-pink-500">
                        表示
                    </label>
                </div>

                <button type="submit"
                        class="w-full bg-pink-600 text-white py-2 rounded hover:bg-pink-700 text-sm font-medium">
                    項目を追加
                </button>
            </form>
        </div>

        <div class="mt-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-xs text-blue-700 dark:text-blue-300">
            <p class="font-bold mb-1">ヒント</p>
            <ul class="space-y-1 list-disc list-inside">
                <li>システム項目は削除できません</li>
                <li>必須/表示はボタンで即時切替可能</li>
                <li>並び順の数値を変更して「並替」ボタンで順番変更</li>
                <li>カスタム項目のデータはuser_form_responsesに保存</li>
            </ul>
        </div>
    </div>

</div>
@endsection
