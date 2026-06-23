@php $isEdit = isset($campaign) && $campaign->exists; @endphp

{{-- 基本情報 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-4">
    <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-4">基本情報</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">案件名 <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="{{ old('title', $campaign->title ?? '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm @error('title') border-red-400 @enderror">
            @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">案件種別 <span class="text-red-500">*</span></label>
            <select name="campaign_type" class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
                <option value="experience" @selected(old('campaign_type', $campaign->campaign_type ?? '') === 'experience')>体験モニター</option>
                <option value="product"    @selected(old('campaign_type', $campaign->campaign_type ?? '') === 'product')>商品モニター</option>
                <option value="recovery"   @selected(old('campaign_type', $campaign->campaign_type ?? '') === 'recovery')>回収サービス</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ステータス <span class="text-red-500">*</span></label>
            <select name="status" class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
                <option value="draft"     @selected(old('status', $campaign->status ?? 'draft') === 'draft')>下書き</option>
                <option value="published" @selected(old('status', $campaign->status ?? '') === 'published')>公開中</option>
                <option value="closed"    @selected(old('status', $campaign->status ?? '') === 'closed')>終了</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">カテゴリ</label>
            <select name="category_id" class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
                <option value="">未選択</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(old('category_id', $campaign->category_id ?? '') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PR媒体</label>
            <input type="text" name="pr_media" value="{{ old('pr_media', $campaign->pr_media ?? '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">案件内容説明</label>
            <textarea name="description" rows="4" class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">{{ old('description', $campaign->description ?? '') }}</textarea>
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">応募条件</label>
            <textarea name="requirements" rows="3" class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">{{ old('requirements', $campaign->requirements ?? '') }}</textarea>
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">注意事項</label>
            <textarea name="notes" rows="3" class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">{{ old('notes', $campaign->notes ?? '') }}</textarea>
        </div>
    </div>
</div>

{{-- 商品情報 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-4">
    <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-4">商品・費用情報</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">商品名</label>
            <input type="text" name="product_name" value="{{ old('product_name', $campaign->product_name ?? '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">商品金額（円）</label>
            <input type="number" name="product_price" value="{{ old('product_price', $campaign->product_price ?? '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm" min="0">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">モニター協力金（円） <span class="text-red-500">*</span></label>
            <input type="number" name="cooperation_fee" value="{{ old('cooperation_fee', $campaign->cooperation_fee ?? 0) }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm @error('cooperation_fee') border-red-400 @enderror" min="0">
            @error('cooperation_fee')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">案件単価（円）</label>
            <input type="number" name="campaign_unit_price" value="{{ old('campaign_unit_price', $campaign->campaign_unit_price ?? '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm" min="0">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">初回購入費（円）</label>
            <input type="number" name="initial_purchase_fee" value="{{ old('initial_purchase_fee', $campaign->initial_purchase_fee ?? '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm" min="0">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">継続購入費（円）</label>
            <input type="number" name="recurring_purchase_fee" value="{{ old('recurring_purchase_fee', $campaign->recurring_purchase_fee ?? '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm" min="0">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">紹介単価（円）</label>
            <input type="number" name="referral_fee" value="{{ old('referral_fee', $campaign->referral_fee ?? 0) }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm" min="0">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">粗利（円）</label>
            <input type="number" name="gross_profit" value="{{ old('gross_profit', $campaign->gross_profit ?? '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">継続率（%）</label>
            <input type="number" name="continuation_rate" value="{{ old('continuation_rate', $campaign->continuation_rate ?? '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm" min="0" max="100" step="0.01">
        </div>
    </div>
</div>

{{-- 募集設定 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-4">
    <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-4">募集設定</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">募集人数 <span class="text-red-500">*</span></label>
            <input type="number" name="capacity" value="{{ old('capacity', $campaign->capacity ?? 1) }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm" min="1">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">打診予定数</label>
            <input type="number" name="solicitation_target" value="{{ old('solicitation_target', $campaign->solicitation_target ?? '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm" min="0">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">男女比目標（テキスト）</label>
            <input type="text" name="target_gender_ratio" value="{{ old('target_gender_ratio', $campaign->target_gender_ratio ?? '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm" placeholder="例: 男:女=3:7">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">目標男性比率（%）</label>
            <input type="number" name="target_male_ratio" value="{{ old('target_male_ratio', $campaign->target_male_ratio ?? '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm" min="0" max="100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">目標女性比率（%）</label>
            <input type="number" name="target_female_ratio" value="{{ old('target_female_ratio', $campaign->target_female_ratio ?? '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm" min="0" max="100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">募集開始日</label>
            <input type="date" name="application_start_at"
                   value="{{ old('application_start_at', isset($campaign) ? $campaign->application_start_at?->format('Y-m-d') : '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">募集終了日</label>
            <input type="date" name="application_end_at"
                   value="{{ old('application_end_at', isset($campaign) ? $campaign->application_end_at?->format('Y-m-d') : '') }}"
                   class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm">
            @error('application_end_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    @if($tags->count())
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">タグ</label>
        <div class="flex flex-wrap gap-2">
            @foreach($tags as $tag)
            <label class="inline-flex items-center gap-1 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                    @checked(in_array($tag->id, old('tags', $campaign->tags->pluck('id')->toArray() ?? [])))>
                {{ $tag->name }}
            </label>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- LINE自動送信設定 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-4">
    <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-1">LINE自動送信設定</h2>
    <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">予約中に移行したユーザーへ案内予定日時に自動送信されるメッセージです。</p>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">モニター案内文</label>
        <textarea name="monitor_invite_message" rows="5"
                  class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm"
                  placeholder="例: ○○モニターのご案内です。&#10;実施時間になりましたらモニターを開始してください。">{{ old('monitor_invite_message', $campaign->monitor_invite_message ?? '') }}</textarea>
        <p class="text-xs text-gray-400 mt-1">予約中ユーザーへ案内日時に自動送信されます</p>
    </div>
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">モニター終了案内文</label>
        <textarea name="monitor_end_message" rows="5"
                  class="w-full border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-2 text-sm"
                  placeholder="例: ○○モニターのご報告をお願いします。&#10;報告ページよりご提出ください。">{{ old('monitor_end_message', $campaign->monitor_end_message ?? '') }}</textarea>
        <p class="text-xs text-gray-400 mt-1">案内終了日時にリマインドとして自動送信されます</p>
    </div>
</div>

{{-- ボタン --}}
<div class="flex gap-3">
    <button type="submit" class="bg-pink-600 text-white px-6 py-2 rounded hover:bg-pink-700 text-sm">
        {{ $isEdit ? '更新する' : '登録する' }}
    </button>
    <a href="{{ route('admin.campaigns.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:underline self-center">キャンセル</a>
</div>
