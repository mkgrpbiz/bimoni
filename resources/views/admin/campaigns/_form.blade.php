@php $isEdit = isset($campaign) && $campaign->exists; @endphp

{{-- 基本情報 --}}
<div class="bg-white rounded-lg shadow p-6 mb-4">
    <h2 class="font-bold text-gray-700 mb-4">基本情報</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">案件名 <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="{{ old('title', $campaign->title ?? '') }}"
                   class="w-full border rounded px-3 py-2 text-sm @error('title') border-red-400 @enderror">
            @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">案件種別 <span class="text-red-500">*</span></label>
            <select name="campaign_type" class="w-full border rounded px-3 py-2 text-sm">
                <option value="experience" @selected(old('campaign_type', $campaign->campaign_type ?? '') === 'experience')>体験モニター</option>
                <option value="product"    @selected(old('campaign_type', $campaign->campaign_type ?? '') === 'product')>商品モニター</option>
                <option value="pr"         @selected(old('campaign_type', $campaign->campaign_type ?? '') === 'pr')>PRモニター</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ステータス <span class="text-red-500">*</span></label>
            <select name="status" class="w-full border rounded px-3 py-2 text-sm">
                <option value="draft"     @selected(old('status', $campaign->status ?? 'draft') === 'draft')>下書き</option>
                <option value="published" @selected(old('status', $campaign->status ?? '') === 'published')>公開中</option>
                <option value="paused"    @selected(old('status', $campaign->status ?? '') === 'paused')>一時停止</option>
                <option value="closed"    @selected(old('status', $campaign->status ?? '') === 'closed')>終了</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">カテゴリ</label>
            <select name="category_id" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">未選択</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(old('category_id', $campaign->category_id ?? '') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">PR媒体</label>
            <select name="pr_media" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">未選択</option>
                <option value="AD"      @selected(old('pr_media', $campaign->pr_media ?? '') === 'AD')>AD</option>
                <option value="IF"      @selected(old('pr_media', $campaign->pr_media ?? '') === 'IF')>IF</option>
                <option value="LINE"    @selected(old('pr_media', $campaign->pr_media ?? '') === 'LINE')>LINE</option>
                <option value="monitor" @selected(old('pr_media', $campaign->pr_media ?? '') === 'monitor')>モニター</option>
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">サムネイル画像</label>
            @if($isEdit && $campaign->thumbnail)
            <div class="mb-2 flex items-center gap-3">
                <img src="{{ asset('storage/' . $campaign->thumbnail) }}" alt="現在の画像"
                     class="w-24 h-24 object-cover rounded border">
                <p class="text-xs text-gray-500">新しい画像を選択すると置き換わります</p>
            </div>
            @endif
            <input type="file" name="thumbnail" accept="image/*"
                   class="w-full border rounded px-3 py-2 text-sm @error('thumbnail') border-red-400 @enderror"
                   id="thumbnail-input"
                   onchange="previewThumbnail(this)">
            <img id="thumbnail-preview" src="" alt="" class="mt-2 w-24 h-24 object-cover rounded border hidden">
            <p class="text-xs text-gray-400 mt-0.5">JPG・PNG・GIF・WEBP、最大5MB</p>
            @error('thumbnail')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">案件内容説明</label>
            <textarea name="description" rows="4" class="w-full border rounded px-3 py-2 text-sm">{{ old('description', $campaign->description ?? '') }}</textarea>
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">注意事項</label>
            <textarea name="notes" rows="3" class="w-full border rounded px-3 py-2 text-sm">{{ old('notes', $campaign->notes ?? '') }}</textarea>
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">解約について</label>
            <textarea name="cancellation_info" rows="3" class="w-full border rounded px-3 py-2 text-sm"
                      placeholder="解約手続きの方法・タイミング等">{{ old('cancellation_info', $campaign->cancellation_info ?? '') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">モニター案内文</label>
            <textarea name="monitor_guide" rows="4" class="w-full border rounded px-3 py-2 text-sm"
                      placeholder="モニター参加者への案内・手順">{{ old('monitor_guide', $campaign->monitor_guide ?? '') }}</textarea>
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">リンク</label>
            <input type="url" name="link" value="{{ old('link', $campaign->link ?? '') }}"
                   placeholder="https://..."
                   class="w-full border rounded px-3 py-2 text-sm @error('link') border-red-400 @enderror">
            @error('link')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

{{-- 詳細情報（費用・支払い） --}}
<div class="bg-white rounded-lg shadow p-6 mb-4">
    <h2 class="font-bold text-gray-700 mb-4">詳細情報</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- Row 1: 案件単価 / モニターコスト / 粗利 --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">案件単価（円）</label>
            <input type="number" name="campaign_unit_price"
                   value="{{ old('campaign_unit_price', $campaign->campaign_unit_price ?? '') }}"
                   class="w-full border rounded px-3 py-2 text-sm" min="0"
                   oninput="calcGross()">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">モニターコスト（自動計算）</label>
            <input type="text" id="f-monitor-cost" readonly
                   class="w-full border rounded px-3 py-2 text-sm bg-gray-50 text-gray-700"
                   value="{{ number_format(isset($campaign) ? $campaign->calculatedMonitorCost() : 0) }}円">
            <p class="text-xs text-gray-400 mt-0.5">初回+継続×継続率+協力金+紹介単価（コース設定が有の場合はコース金額×％の加重平均+協力金+紹介単価）</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">粗利（自動計算）</label>
            <input type="number" name="gross_profit" id="f-gross" readonly
                   value="{{ old('gross_profit', $campaign->gross_profit ?? '') }}"
                   class="w-full border rounded px-3 py-2 text-sm bg-gray-50">
            <p class="text-xs text-gray-400 mt-0.5">案件単価 − モニターコスト</p>
        </div>

        {{-- Row 2: 初回購入費 / 継続購入費 / 紹介報酬 --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">初回購入費（円）</label>
            <input type="number" name="initial_purchase_fee" id="f-initial"
                   value="{{ old('initial_purchase_fee', $campaign->initial_purchase_fee ?? '') }}"
                   class="w-full border rounded px-3 py-2 text-sm" min="0"
                   oninput="updateCoopLabels(); calcGross()">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">継続購入費（円）</label>
            <input type="number" name="recurring_purchase_fee" id="f-recurring"
                   value="{{ old('recurring_purchase_fee', $campaign->recurring_purchase_fee ?? '') }}"
                   class="w-full border rounded px-3 py-2 text-sm" min="0"
                   oninput="updateCoopLabels(); calcGross()">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">紹介単価（円）</label>
            <select name="referral_fee" id="f-referral" onchange="calcGross()"
                    class="w-full border rounded px-3 py-2 text-sm">
                @foreach([0 => 'なし', 500 => '500円', 1000 => '1,000円'] as $val => $label)
                <option value="{{ $val }}" @selected((int) old('referral_fee', $campaign->referral_fee ?? 0) === $val)>
                    {{ $label }}
                </option>
                @endforeach
            </select>
        </div>

        {{-- Row 3: モニター協力金 / 回収前提 --}}
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">モニター協力金（+○円部分）</label>
            <div class="flex items-center gap-1">
                <span class="text-sm text-gray-500 whitespace-nowrap">初回購入費(<span id="lbl-initial">{{ number_format($campaign->initial_purchase_fee ?? 0) }}</span>円)+</span>
                <input type="number" name="cooperation_fee" id="f-coop"
                       value="{{ old('cooperation_fee', $campaign->cooperation_fee ?? '') }}"
                       placeholder="空欄=非表示"
                       class="flex-1 border rounded px-3 py-2 text-sm @error('cooperation_fee') border-red-400 @enderror"
                       min="0" oninput="calcGross()">
                <span class="text-sm text-gray-500">円</span>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">表示例：初回購入費(5,000円)+200円</p>
            @error('cooperation_fee')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">回収前提（継続）</label>
            <select name="collection_requirement" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">未設定</option>
                <option value="回収前提" @selected(old('collection_requirement', $campaign->collection_requirement ?? '') === '回収前提')>回収前提</option>
                <option value="回収不要" @selected(old('collection_requirement', $campaign->collection_requirement ?? '') === '回収不要')>回収不要</option>
            </select>
        </div>

        {{-- Row 4: 継続モニター協力金 / 回収個数判定 --}}
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">継続モニター協力金（+○円部分）</label>
            <div class="flex items-center gap-1">
                <span class="text-sm text-gray-500 whitespace-nowrap">継続購入費(<span id="lbl-recurring">{{ number_format($campaign->recurring_purchase_fee ?? 0) }}</span>円)+</span>
                <input type="number" name="continuation_cooperation_fee" id="f-cont-coop"
                       value="{{ old('continuation_cooperation_fee', $campaign->continuation_cooperation_fee ?? '') }}"
                       class="flex-1 border rounded px-3 py-2 text-sm"
                       min="0" oninput="calcGross()" placeholder="空欄=継続購入費のみ表示">
                <span class="text-sm text-gray-500">円</span>
            </div>
            <p class="text-xs text-gray-400 mt-0.5">空欄の場合「継続購入費(○円)」のみ表示</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">回収個数判定（継続）</label>
            <select name="collection_count_judgment" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">未設定</option>
                <option value="1" @selected((string) old('collection_count_judgment', $campaign->collection_count_judgment ?? '') === '1')>1個</option>
                <option value="2" @selected((string) old('collection_count_judgment', $campaign->collection_count_judgment ?? '') === '2')>2個</option>
                <option value="3" @selected((string) old('collection_count_judgment', $campaign->collection_count_judgment ?? '') === '3')>3個</option>
            </select>
        </div>

        {{-- Row 4-2: 継続条件 --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">継続条件</label>
            <select name="continuation_condition" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">未設定</option>
                <option value="2回前提" @selected(old('continuation_condition', $campaign->continuation_condition ?? '') === '2回前提')>2回前提</option>
                <option value="3回前提" @selected(old('continuation_condition', $campaign->continuation_condition ?? '') === '3回前提')>3回前提</option>
            </select>
            <p class="text-xs text-gray-400 mt-0.5">2回前提/3回前提の場合、応募フォームの継続希望確認を非表示にし、応募時点で継続希望を自動的にOKにします</p>
        </div>

        {{-- Row 5: 締日 / 支払い日 --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">締日</label>
            <select name="closing_date" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">未選択</option>
                <option value="20日"  @selected(old('closing_date', $campaign->closing_date ?? '') === '20日')>20日</option>
                <option value="25日"  @selected(old('closing_date', $campaign->closing_date ?? '') === '25日')>25日</option>
                <option value="月末"  @selected(old('closing_date', $campaign->closing_date ?? '') === '月末')>月末</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">支払い日</label>
            <select name="payment_timing" class="w-full border rounded px-3 py-2 text-sm">
                <option value="">未選択</option>
                <option value="翌月末"   @selected(old('payment_timing', $campaign->payment_timing ?? '') === '翌月末')>翌月末</option>
                <option value="翌々月末" @selected(old('payment_timing', $campaign->payment_timing ?? '') === '翌々月末')>翌々月末</option>
            </select>
        </div>
    </div>
</div>

{{-- 募集設定 --}}
<div class="bg-white rounded-lg shadow p-6 mb-4">
    <h2 class="font-bold text-gray-700 mb-4">募集設定</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">目標継続率（%）</label>
            <input type="number" name="continuation_rate" id="f-rate"
                   value="{{ old('continuation_rate', $campaign->continuation_rate ?? '') }}"
                   class="w-full border rounded px-3 py-2 text-sm" min="0" max="100" step="0.01"
                   oninput="calcGross()">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">目標男性比率（%）</label>
            <input type="number" name="target_male_ratio"
                   value="{{ old('target_male_ratio', $campaign->target_male_ratio ?? '') }}"
                   class="w-full border rounded px-3 py-2 text-sm" min="0" max="100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">目標女性比率（%）</label>
            <input type="number" name="target_female_ratio"
                   value="{{ old('target_female_ratio', $campaign->target_female_ratio ?? '') }}"
                   class="w-full border rounded px-3 py-2 text-sm" min="0" max="100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">応募上限人数 <span class="text-gray-400 text-xs">任意・上限到達で自動一時停止</span></label>
            <input type="number" name="capacity"
                   value="{{ old('capacity', $campaign->capacity ?? '') }}"
                   placeholder="上限なし"
                   class="w-full border rounded px-3 py-2 text-sm" min="1">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">募集開始日</label>
            <input type="date" name="application_start_at"
                   value="{{ old('application_start_at', isset($campaign) ? $campaign->application_start_at?->format('Y-m-d') : '') }}"
                   class="w-full border rounded px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">募集終了日</label>
            <input type="date" name="application_end_at"
                   value="{{ old('application_end_at', isset($campaign) ? $campaign->application_end_at?->format('Y-m-d') : '') }}"
                   class="w-full border rounded px-3 py-2 text-sm">
            @error('application_end_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    @if($tags->count())
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">タグ</label>
        <div class="flex flex-wrap gap-2">
            @foreach($tags as $tag)
            <label class="inline-flex items-center gap-1 text-sm text-gray-700">
                <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                    @checked(in_array($tag->id, old('tags', $campaign->tags->pluck('id')->toArray() ?? [])))>
                {{ $tag->name }}
            </label>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- コース指定設定 --}}
@php
    $courseRows = old('courses') ?: (($campaign->courses ?? collect())->map(fn($c) => [
        'name'                 => $c->name,
        'initial_purchase_fee' => $c->initial_purchase_fee,
        'course_type'          => $c->course_type,
        'continuation_count'   => $c->continuation_count,
        'continuation_fee_2'   => $c->continuation_fee_2,
        'continuation_fee_3'   => $c->continuation_fee_3,
        'percentage'           => $c->percentage,
        'invite_message'       => $c->invite_message,
    ])->values()->toArray());
    $courseEnabled = old('course_settings_enabled', ($campaign->course_settings_enabled ?? false) ? '1' : '0');
@endphp
<script>
    window.__campaignCourseInit    = @json($courseRows);
    window.__campaignCourseEnabled = @json($courseEnabled);
</script>
<div class="bg-white rounded-lg shadow p-6 mb-4" x-data="courseSettings()">
    <h2 class="font-bold text-gray-700 mb-4">コース指定設定</h2>
    <div class="flex items-end gap-4 mb-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">コース設定</label>
            <select name="course_settings_enabled" x-model="enabled" onchange="calcGross()" class="w-full md:w-48 border rounded px-3 py-2 text-sm">
                <option value="0">無</option>
                <option value="1">有</option>
            </select>
        </div>
        <div x-show="enabled === '1'">
            <label class="block text-sm font-medium text-gray-700 mb-1">通常案内の割合（％）</label>
            <input type="number" name="course_normal_percentage" value="{{ old('course_normal_percentage', $campaign->course_normal_percentage ?? '') }}"
                   min="0" max="100" step="0.01" oninput="calcGross()" placeholder="例: 80"
                   class="w-full md:w-40 border rounded px-3 py-2 text-sm">
            <p class="text-xs text-gray-400 mt-0.5">詳細情報の初回購入費・継続購入費等（既定の案内）が占める割合。この割合と各コースの目標％の合計が100％になるように設定してください。</p>
        </div>
    </div>
    <template x-if="enabled === '1'">
        <div class="space-y-3">
            <template x-for="(course, index) in courses" :key="index">
                <div class="course-row border rounded-lg p-3 space-y-2">
                    {{-- 1行目: コース名・初回購入費・目標% --}}
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
                        <div class="md:col-span-5">
                            <label class="block text-xs text-gray-500 mb-1">
                                コース名 <span class="text-gray-400 font-mono" x-text="'（コード: ' + courseCode('コース名' + (index+2)) + '）'"></span>
                            </label>
                            <input type="text" :name="`courses[${index}][name]`" x-model="course.name"
                                   class="w-full border rounded px-2 py-1.5 text-sm">
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs text-gray-500 mb-1">
                                初回購入費（円） <span class="text-gray-400 font-mono" x-text="'（コード: ' + courseCode('初回購入費' + (index+2)) + '）'"></span>
                            </label>
                            <input type="number" :name="`courses[${index}][initial_purchase_fee]`" x-model="course.initial_purchase_fee"
                                   min="0" oninput="calcGross()" class="w-full border rounded px-2 py-1.5 text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">目標％</label>
                            <input type="number" :name="`courses[${index}][percentage]`" x-model="course.percentage"
                                   min="0" max="100" step="0.01" oninput="calcGross()" class="w-full border rounded px-2 py-1.5 text-sm">
                        </div>
                        <div class="md:col-span-1 flex items-end">
                            <button type="button" @click="removeCourse(index)" class="text-red-500 text-xs hover:underline pb-2">削除</button>
                        </div>
                    </div>
                    {{-- 2行目: 単発/継続・継続回数・継続購入費2/3 --}}
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2">
                        <div class="md:col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">単発/継続</label>
                            <select :name="`courses[${index}][course_type]`" x-model="course.course_type"
                                    onchange="calcGross()" class="w-full border rounded px-2 py-1.5 text-sm">
                                <option value="単発">単発</option>
                                <option value="継続">継続</option>
                            </select>
                        </div>
                        <template x-if="course.course_type === '継続'">
                            <div class="md:col-span-2">
                                <label class="block text-xs text-gray-500 mb-1">継続回数</label>
                                <select :name="`courses[${index}][continuation_count]`" x-model="course.continuation_count"
                                        onchange="calcGross()" class="w-full border rounded px-2 py-1.5 text-sm">
                                    <option value="2">2回</option>
                                    <option value="3">3回</option>
                                </select>
                            </div>
                        </template>
                        <template x-if="course.course_type === '継続'">
                            <div class="md:col-span-2">
                                <label class="block text-xs text-gray-500 mb-1">
                                    継続購入費2（円） <span class="text-gray-400 font-mono" x-text="'（コード: ' + courseCode('継続購入費' + (index+2) + '-2') + '）'"></span>
                                </label>
                                <input type="number" :name="`courses[${index}][continuation_fee_2]`" x-model="course.continuation_fee_2"
                                       min="0" oninput="calcGross()" class="w-full border rounded px-2 py-1.5 text-sm">
                            </div>
                        </template>
                        <template x-if="course.course_type === '継続' && String(course.continuation_count) === '3'">
                            <div class="md:col-span-2">
                                <label class="block text-xs text-gray-500 mb-1">
                                    継続購入費3（円） <span class="text-gray-400 font-mono" x-text="'（コード: ' + courseCode('継続購入費' + (index+2) + '-3') + '）'"></span>
                                </label>
                                <input type="number" :name="`courses[${index}][continuation_fee_3]`" x-model="course.continuation_fee_3"
                                       min="0" oninput="calcGross()" class="w-full border rounded px-2 py-1.5 text-sm">
                            </div>
                        </template>
                    </div>
                    {{-- 3行目: モニター案内メッセージ --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">モニター案内メッセージ（打診時にこのコースを選ぶと使用）</label>
                        <div class="bg-gray-50 border border-gray-200 rounded p-2 mb-1 text-xs text-gray-600">
                            <p class="font-medium text-gray-700">このコース専用コード（コースごとに番号が変わります）</p>
                            <div class="grid grid-cols-2 gap-0.5 font-mono">
                                <span x-text="courseCode('コース名' + (index+2))"></span><span class="text-gray-400">→ このコースのコース名</span>
                                <span x-text="courseCode('初回購入費' + (index+2))"></span><span class="text-gray-400">→ このコースの初回購入費</span>
                                <template x-if="course.course_type === '継続'">
                                    <span x-text="courseCode('継続購入費' + (index+2) + '-2')"></span>
                                </template>
                                <template x-if="course.course_type === '継続'">
                                    <span class="text-gray-400">→ このコースの継続購入費2</span>
                                </template>
                                <template x-if="course.course_type === '継続' && String(course.continuation_count) === '3'">
                                    <span x-text="courseCode('継続購入費' + (index+2) + '-3')"></span>
                                </template>
                                <template x-if="course.course_type === '継続' && String(course.continuation_count) === '3'">
                                    <span class="text-gray-400">→ このコースの継続購入費3</span>
                                </template>
                            </div>
                            <p class="mt-1">下記の案件共通コード（@{{商品名}} @{{モニター協力金}} @{{解約について}} @{{モニター案内文}} @{{リンク}} @{{案内日時}}）も使えます。</p>
                        </div>
                        <textarea :name="`courses[${index}][invite_message]`" x-model="course.invite_message" rows="2"
                                  class="w-full border rounded px-2 py-1.5 text-sm" placeholder="空欄=案件共通のモニター案内メッセージを使用"></textarea>
                    </div>
                </div>
            </template>
            <button type="button" @click="addCourse()"
                    class="bg-gray-100 text-gray-700 px-3 py-1.5 rounded text-sm hover:bg-gray-200">+ 行追加</button>
            <p class="text-xs text-gray-400">通常案内％+各コースの目標％の合計が100%になるようにしてください。モニターコストは「通常案内の既定コスト×通常割合」＋「各コースのコスト×目標％」の加重平均（単発=初回購入費のみ、継続=初回購入費+継続購入費2〈3回の場合はさらに+継続購入費3〉）で自動計算されます。「有」にした案件は打診時にコース選択プルダウンが表示され、指定したコースの「モニター案内メッセージ」が案件共通の案内文の代わりに送信されます（モニター終了案内文は共通のまま）。</p>
        </div>
    </template>
</div>

{{-- 応募フォームはフォーム設定で一元管理（admin/form-fields?tab=application） --}}

{{-- 報告フォームはフォーム設定で一元管理（admin/form-fields?tab=report） --}}

{{-- LINE自動送信設定 --}}
<div class="bg-white rounded-lg shadow p-6 mb-4">
    <h2 class="font-bold text-gray-700 mb-1">LINE自動送信設定</h2>
    <p class="text-xs text-gray-700 mb-2">予約中に移行したユーザーへ案内予定日時に自動送信されるメッセージです。</p>

    <div class="bg-gray-50 border border-gray-200 rounded p-3 mb-4 text-xs text-gray-600">
        <p class="font-medium text-gray-700 mb-1">使用できるコード（自動で値に置換されます）</p>
        <div class="grid grid-cols-2 gap-1 font-mono">
            <span>@{{商品名}}</span><span class="text-gray-400">→ 商品名</span>
            <span>@{{初回購入費}}</span><span class="text-gray-400">→ 初回購入費（円）</span>
            <span>@{{モニター協力金}}</span><span class="text-gray-400">→ モニター協力金（円）</span>
            <span>@{{解約について}}</span><span class="text-gray-400">→ 解約についての内容</span>
            <span>@{{モニター案内文}}</span><span class="text-gray-400">→ モニター案内文の内容</span>
            <span>@{{リンク}}</span><span class="text-gray-400">→ リンクURL</span>
            <span>@{{案内日時}}</span><span class="text-gray-400">→ 案内日時（例: 7月4日 10:00〜11:00）</span>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">モニター案内メッセージ</label>
        <textarea name="monitor_invite_message" rows="6"
                  class="w-full border rounded px-3 py-2 text-sm font-mono"
                  placeholder="例: @{{商品名}}のモニターご案内です。&#10;@{{モニター案内文}}&#10;詳細はこちら: @{{リンク}}">{{ old('monitor_invite_message', $campaign->monitor_invite_message ?? '') }}</textarea>
    </div>

    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">案内動画</label>
        @if($isEdit && $campaign->monitor_video)
        <div class="mb-2 flex items-center gap-3">
            <video src="{{ asset('storage/' . $campaign->monitor_video) }}"
                   controls class="w-48 rounded border"></video>
            <p class="text-xs text-gray-500">新しい動画を選択すると置き換わります</p>
        </div>
        @endif
        <input type="file" name="monitor_video" accept="video/mp4,video/quicktime,video/avi,video/webm"
               class="w-full border rounded px-3 py-2 text-sm">
        <p class="text-xs text-gray-400 mt-0.5">MP4・MOV・AVI・WebM、最大200MB</p>
        @error('monitor_video')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">動画サムネイル画像 <span class="text-gray-400 text-xs font-normal">※LINE送信時のプレビュー画像（必須）</span></label>
        @if($isEdit && $campaign->monitor_video_thumbnail)
        <div class="mb-2 flex items-center gap-3">
            <img src="{{ asset('storage/' . $campaign->monitor_video_thumbnail) }}"
                 class="w-24 h-16 object-cover rounded border" alt="動画サムネイル">
            <p class="text-xs text-gray-500">新しい画像を選択すると置き換わります</p>
        </div>
        @endif
        <input type="file" name="monitor_video_thumbnail" accept="image/*"
               class="w-full border rounded px-3 py-2 text-sm">
        <p class="text-xs text-gray-400 mt-0.5">JPEG・PNG・GIF、最大5MB。動画未設定の場合は不要です。</p>
        @error('monitor_video_thumbnail')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">モニター終了案内文</label>
        <textarea name="monitor_end_message" rows="5"
                  class="w-full border rounded px-3 py-2 text-sm font-mono"
                  placeholder="例: @{{商品名}}モニターへのご参加ありがとうございました。&#10;ご報告をお願いします。">{{ old('monitor_end_message', $campaign->monitor_end_message ?? '') }}</textarea>
    </div>
</div>

{{-- ボタン --}}
<div class="flex gap-3">
    <button type="submit" class="bg-pink-500 text-white px-6 py-2 rounded hover:bg-pink-600 text-sm">
        {{ $isEdit ? '更新する' : '登録する' }}
    </button>
    <a href="{{ route('admin.campaigns.index') }}"
       class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 text-sm self-center">キャンセル</a>
</div>

<script>
function previewThumbnail(input) {
    const preview = document.getElementById('thumbnail-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.classList.remove('hidden'); };
        reader.readAsDataURL(input.files[0]);
    }
}

function updateCoopLabels() {
    const initial   = parseFloat(document.getElementById('f-initial')?.value)   || 0;
    const recurring = parseFloat(document.getElementById('f-recurring')?.value) || 0;
    const lblInit = document.getElementById('lbl-initial');
    const lblRec  = document.getElementById('lbl-recurring');
    if (lblInit) lblInit.textContent = Math.round(initial).toLocaleString();
    if (lblRec)  lblRec.textContent  = Math.round(recurring).toLocaleString();
}

function calcMonitorCost() {
    const rate      = parseFloat(document.getElementById('f-rate')?.value)      || 0;
    const coop      = parseFloat(document.getElementById('f-coop')?.value)      || 0;
    const referral  = parseFloat(document.getElementById('f-referral')?.value)  || 0;
    const initial   = parseFloat(document.getElementById('f-initial')?.value)   || 0;
    const recurring = parseFloat(document.getElementById('f-recurring')?.value) || 0;
    const normalCost = initial + recurring * (rate / 100);
    const courseEnabled = document.querySelector('[name="course_settings_enabled"]')?.value === '1';

    let cost;
    if (courseEnabled) {
        const normalPct = parseFloat(document.querySelector('[name="course_normal_percentage"]')?.value) || 0;
        let weighted = normalCost * (normalPct / 100);
        document.querySelectorAll('.course-row').forEach(row => {
            const initialFee = parseFloat(row.querySelector('[name$="[initial_purchase_fee]"]')?.value) || 0;
            const type       = row.querySelector('[name$="[course_type]"]')?.value;
            const pct        = parseFloat(row.querySelector('[name$="[percentage]"]')?.value) || 0;
            let courseCost = initialFee;
            if (type === '継続') {
                const countSelect = row.querySelector('[name$="[continuation_count]"]');
                const count = countSelect ? parseInt(countSelect.value) : null;
                const fee2 = parseFloat(row.querySelector('[name$="[continuation_fee_2]"]')?.value) || 0;
                const fee3 = parseFloat(row.querySelector('[name$="[continuation_fee_3]"]')?.value) || 0;
                courseCost += fee2;
                if (count === 3) courseCost += fee3;
            }
            weighted += courseCost * (pct / 100);
        });
        cost = weighted + coop + referral;
    } else {
        cost = normalCost + coop + referral;
    }

    const el = document.getElementById('f-monitor-cost');
    if (el) el.value = Math.round(cost).toLocaleString() + '円';
    return cost;
}

function courseSettings() {
    return {
        enabled: window.__campaignCourseEnabled || '0',
        courses: (window.__campaignCourseInit && window.__campaignCourseInit.length) ? window.__campaignCourseInit : [],
        addCourse() {
            this.courses.push({
                name: '', initial_purchase_fee: '', course_type: '単発',
                continuation_count: '2', continuation_fee_2: '', continuation_fee_3: '',
                percentage: '', invite_message: '',
            });
            this.$nextTick(() => calcGross());
        },
        removeCourse(index) {
            this.courses.splice(index, 1);
            this.$nextTick(() => calcGross());
        },
    };
}

function courseCode(inner) {
    const b = String.fromCharCode(123);
    const e = String.fromCharCode(125);
    return b + b + inner + e + e;
}

function calcGross() {
    const unitPrice   = parseFloat(document.querySelector('[name="campaign_unit_price"]')?.value) || 0;
    const monitorCost = calcMonitorCost();
    const gross       = unitPrice - monitorCost;
    const el = document.getElementById('f-gross');
    if (el) el.value = Math.round(gross);
}
</script>

