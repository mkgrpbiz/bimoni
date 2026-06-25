@extends('layouts.admin')
@section('title', 'フォーム設定')
@section('content')
<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">フォーム設定</h1>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4 text-sm">{{ session('error') }}</div>
@endif

{{-- タブ --}}
<div class="flex gap-1 mb-6 border-b border-gray-200 dark:border-gray-700">
    @foreach(['application' => '応募', 'report' => '報告', 'legal' => '規約・PP'] as $key => $label)
    <a href="?tab={{ $key }}"
       class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors
              {{ $tab === $key ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        {{ $label }}フォーム
    </a>
    @endforeach
</div>

@if($tab === 'legal')
{{-- ■ 利用規約・プライバシーポリシー --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @foreach([['terms', $terms], ['privacy', $privacy]] as [$slug, $page])
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-4">{{ $page->title }}</h2>
        <form method="POST" action="{{ route('admin.form_fields.legal', $slug) }}">
            @csrf @method('PATCH')
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">タイトル</label>
                <input type="text" name="title" value="{{ old('title', $page->title) }}" required
                       class="w-full border rounded px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
            </div>
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1">本文（Markdownまたはプレーンテキスト）</label>
                <textarea name="content" rows="15"
                          class="w-full border rounded px-3 py-2 text-sm font-mono dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">{{ old('content', $page->content) }}</textarea>
            </div>
            <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">保存する</button>
        </form>
    </div>
    @endforeach
</div>

@else
{{-- ■ フォームフィールド管理（registration / application / report 共通UI） --}}
@php
$typeLabels = [
    'text'                 => 'テキスト',
    'textarea'             => 'テキストエリア',
    'date'                 => '日付',
    'radio'                => '単一選択',
    'checkbox'             => '複数選択',
    'select'               => 'ドロップダウン',
    'tel'                  => '電話番号',
    'email'                => 'メールアドレス',
    'number'               => '数値',
    'image'                => '画像添付',
    'campaign_thumbnail'          => '🖼 案件画像',
    'campaign_description'        => '📄 説明文',
    'campaign_requirements'       => '📋 応募条件',
    'campaign_notes'              => '⚠ 注意事項',
    'campaign_initial_fee'        => '💴 初回購入費',
    'campaign_recurring_fee'      => '💴 継続購入費',
    'campaign_cooperation_fee'    => '💰 モニター協力金',
    'application_available_times'     => '🕐 実施可能時間',
    'application_wants_continuation'  => '🔁 継続可否',
];
@endphp

{{-- プレビューボタン --}}
<div class="mb-4 flex justify-end">
    <button onclick="document.getElementById('previewModal').style.display='flex'"
            class="inline-flex items-center gap-2 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-50 shadow-sm">
        👁 フォームをプレビュー
    </button>
</div>

{{-- プレビューモーダル --}}
<div id="previewModal"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center;">
    <div style="background:#f9fafb;width:100%;max-width:420px;max-height:90vh;border-radius:16px;overflow-y:auto;position:relative;margin:0 16px;">
        {{-- モーダルヘッダー --}}
        <div style="background:#ec4899;color:#fff;padding:12px 16px;border-radius:16px 16px 0 0;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-weight:bold;font-size:15px;">
                📋 {{ ['registration'=>'会員登録','application'=>'応募','report'=>'報告'][$tab] ?? $tab }}フォームプレビュー
            </span>
            <button onclick="document.getElementById('previewModal').style.display='none'"
                    style="background:none;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">✕</button>
        </div>
        {{-- フィールド一覧 --}}
        <div style="padding:16px;display:flex;flex-direction:column;gap:16px;">
            @forelse($fields->where('is_visible', true)->sortBy('sort_order') as $field)
                @include('member._form_field', ['field' => $field])
            @empty
                <p style="text-align:center;color:#9ca3af;padding:32px 0;">表示中のフィールドがありません</p>
            @endforelse
            <div style="padding-top:8px;">
                <div style="width:100%;background:#ec4899;color:#fff;padding:14px;border-radius:12px;text-align:center;font-weight:bold;font-size:15px;">
                    送信する（プレビュー）
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- フィールド一覧 --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3 text-left w-8">順</th>
                        <th class="px-4 py-3 text-left">ラベル / 説明</th>
                        <th class="px-4 py-3 text-left">種別</th>
                        <th class="px-4 py-3 text-center">必須</th>
                        <th class="px-4 py-3 text-center">表示</th>
                        <th class="px-4 py-3 text-left">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @forelse($fields as $field)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-3 text-center text-gray-500">{{ $field->sort_order }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium dark:text-gray-200">{{ $field->label }}
                                @if($field->is_system)
                                    <span class="ml-1 text-xs bg-blue-100 text-blue-600 px-1.5 rounded">システム</span>
                                @endif
                            </p>
                            @if($field->description)
                                <p class="text-xs text-gray-500 mt-0.5">{{ $field->description }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $typeLabels[$field->type] ?? $field->type }}</td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" action="{{ route('admin.form_fields.toggle', $field) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="field" value="is_required">
                                <button type="submit" class="text-xs px-2 py-0.5 rounded {{ $field->is_required ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                                    {{ $field->is_required ? '必須' : '任意' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" action="{{ route('admin.form_fields.toggle', $field) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="field" value="is_visible">
                                <button type="submit" class="text-xs px-2 py-0.5 rounded {{ $field->is_visible ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600' }}">
                                    {{ $field->is_visible ? '表示' : '非表示' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('admin.form_fields.update', $field) }}" class="flex items-center gap-1">
                                    @csrf @method('PATCH')
                                    <input type="number" name="sort_order" value="{{ $field->sort_order }}"
                                           class="w-12 border rounded px-1 py-0.5 text-xs text-center dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                    <input type="hidden" name="label" value="{{ $field->label }}">
                                    <button type="submit" class="text-xs bg-pink-500 text-white px-1.5 py-0.5 rounded hover:bg-pink-600">並替</button>
                                </form>
                                @if(!$field->is_system)
                                <form method="POST" action="{{ route('admin.form_fields.destroy', $field) }}"
                                      onsubmit="return confirm('削除しますか？')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs bg-red-500 text-white px-1.5 py-0.5 rounded hover:bg-red-600">削除</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">フィールドがありません</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tab === 'application')
        <p class="text-xs text-gray-500 mt-2">※ここで作成したフィールドは各案件の編集画面で案件ごとに有効化できます。</p>
        @endif
    </div>

    {{-- 新規追加フォーム --}}
    <div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-4">項目を追加</h2>
            <form method="POST" action="{{ route('admin.form_fields.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="form_type" value="{{ $tab }}">

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">ラベル <span class="text-red-400">*</span></label>
                    <input type="text" name="label" required value="{{ old('label') }}"
                           class="w-full border rounded px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                    @error('label')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">説明文（ラベル下に表示）</label>
                    <textarea name="description" rows="2"
                              class="w-full border rounded px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                              placeholder="入力例や補足説明">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">入力形式 <span class="text-red-400">*</span></label>
                    <select name="type" required class="w-full border rounded px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                        <option value="text">テキスト</option>
                        <option value="textarea">テキストエリア</option>
                        <option value="date">日付</option>
                        <option value="radio">単一選択（ラジオ）</option>
                        <option value="checkbox">複数選択（チェックボックス）</option>
                        <option value="select">ドロップダウン</option>
                        <option value="tel">電話番号</option>
                        <option value="email">メールアドレス</option>
                        <option value="number">数値</option>
                        <option value="image">画像添付</option>
                        @if($tab === 'application')
                        <optgroup label="── 案件情報（表示用）──">
                            <option value="campaign_thumbnail">🖼 案件画像</option>
                            <option value="campaign_description">📄 説明文</option>
                            <option value="campaign_requirements">📋 応募条件</option>
                            <option value="campaign_notes">⚠ 注意事項</option>
                            <option value="campaign_initial_fee">💴 初回購入費</option>
                            <option value="campaign_recurring_fee">💴 継続購入費</option>
                            <option value="campaign_cooperation_fee">💰 モニター協力金</option>
                        </optgroup>
                        <optgroup label="── 応募情報（入力用）──">
                            <option value="application_available_times">🕐 実施可能時間</option>
                            <option value="application_wants_continuation">🔁 継続可否</option>
                        </optgroup>
                        @endif
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">選択肢（ラジオ/チェック/セレクト用）</label>
                    <textarea name="options" rows="4"
                              class="w-full border rounded px-3 py-2 text-sm font-mono dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                              placeholder="1行に1つ入力">{{ old('options') }}</textarea>
                </div>

                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="is_required" value="1" {{ old('is_required') ? 'checked' : '' }} class="accent-pink-500">
                        必須
                    </label>
                </div>

                <button type="submit" class="w-full bg-pink-600 text-white py-2 rounded hover:bg-pink-700 text-sm font-medium">
                    項目を追加
                </button>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
