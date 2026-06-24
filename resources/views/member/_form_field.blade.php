@php $inputName = 'field_' . $field->field_key; $currentVal = $currentVal ?? null; @endphp
<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">
        {{ $field->label }}
        @if($field->is_required)
            <span class="text-red-500 text-xs ml-1">必須</span>
        @else
            <span class="text-gray-400 text-xs ml-1">任意</span>
        @endif
    </label>
    @if($field->description)
        <p class="text-xs text-gray-500 mb-1.5">{{ $field->description }}</p>
    @endif

    @if($field->type === 'text')
        <input type="text" name="{{ $inputName }}" value="{{ old($inputName, $currentVal) }}"
               class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error($inputName) border-red-400 @enderror">
    @elseif($field->type === 'textarea')
        <textarea name="{{ $inputName }}" rows="3"
                  class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error($inputName) border-red-400 @enderror">{{ old($inputName, $currentVal) }}</textarea>
    @elseif($field->type === 'date')
        <input type="date" name="{{ $inputName }}" value="{{ old($inputName, $currentVal) }}"
               class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error($inputName) border-red-400 @enderror">
    @elseif($field->type === 'tel')
        <input type="tel" name="{{ $inputName }}" value="{{ old($inputName, $currentVal) }}"
               placeholder="090-0000-0000"
               class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error($inputName) border-red-400 @enderror">
    @elseif($field->type === 'email')
        <input type="email" name="{{ $inputName }}" value="{{ old($inputName, $currentVal) }}"
               class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error($inputName) border-red-400 @enderror">
    @elseif($field->type === 'number')
        <input type="number" name="{{ $inputName }}" value="{{ old($inputName, $currentVal) }}"
               class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm @error($inputName) border-red-400 @enderror">
    @elseif($field->type === 'radio' && $field->options)
        <div class="space-y-2">
            @foreach($field->options as $option)
            <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:border-pink-300">
                <input type="radio" name="{{ $inputName }}" value="{{ $option['value'] }}"
                       {{ old($inputName, $currentVal) == $option['value'] ? 'checked' : '' }}
                       class="accent-pink-500">
                <span class="text-sm text-gray-700">{{ $option['label'] }}</span>
            </label>
            @endforeach
        </div>
    @elseif($field->type === 'checkbox' && $field->options)
        @php $checked = old($inputName, $currentVal ? explode(',', $currentVal) : []); @endphp
        <div class="space-y-2">
            @foreach($field->options as $option)
            <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:border-pink-300">
                <input type="checkbox" name="{{ $inputName }}[]" value="{{ $option['value'] }}"
                       {{ in_array($option['value'], (array)$checked) ? 'checked' : '' }}
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
                <option value="{{ $option['value'] }}" {{ old($inputName, $currentVal) == $option['value'] ? 'selected' : '' }}>
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
    @elseif($field->type === 'application_wants_continuation')
        @php $wc = old($inputName, $currentVal); @endphp
        <div class="space-y-2">
            <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:border-pink-300">
                <input type="radio" name="{{ $inputName }}" value="1" {{ $wc == '1' ? 'checked' : '' }} class="accent-pink-500">
                <span class="text-sm text-gray-700">継続希望する</span>
            </label>
            <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:border-pink-300">
                <input type="radio" name="{{ $inputName }}" value="0" {{ $wc === '0' ? 'checked' : '' }} class="accent-pink-500">
                <span class="text-sm text-gray-700">継続不要</span>
            </label>
        </div>
    @elseif($field->type === 'application_available_times')
        @php
        $times   = ['10:00〜13:00', '14:00〜17:00', '18:00〜20:00', '21:00〜24:00'];
        $checked = old($inputName, $currentVal ? explode(',', $currentVal) : []);
        @endphp
        <div class="space-y-2">
            @foreach($times as $time)
            <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:border-pink-300">
                <input type="checkbox" name="{{ $inputName }}[]" value="{{ $time }}"
                       {{ in_array($time, (array)$checked) ? 'checked' : '' }}
                       class="accent-pink-500">
                <span class="text-sm text-gray-700">{{ $time }}</span>
            </label>
            @endforeach
        </div>
    @elseif(str_starts_with($field->type, 'campaign_'))
        @php $c = $campaign ?? null; @endphp
        @if($c)
            @if($field->type === 'campaign_thumbnail')
                <div class="w-full aspect-video bg-gradient-to-br from-pink-100 to-pink-200 rounded-xl overflow-hidden">
                    @if($c->thumbnail)
                        <img src="{{ asset('storage/' . $c->thumbnail) }}" alt="{{ $c->title }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-5xl">💄</div>
                    @endif
                </div>
            @elseif($field->type === 'campaign_description' && $c->description)
                <div class="bg-white rounded-xl border border-gray-100 p-4">
                    <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap">{{ $c->description }}</p>
                </div>
            @elseif($field->type === 'campaign_requirements' && $c->requirements)
                <div class="bg-white rounded-xl border border-gray-100 p-4">
                    <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-wrap">{{ $c->requirements }}</p>
                </div>
            @elseif($field->type === 'campaign_notes' && $c->notes)
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <p class="text-sm text-amber-700 leading-relaxed whitespace-pre-wrap">{{ $c->notes }}</p>
                </div>
            @elseif($field->type === 'campaign_initial_fee')
                <div class="flex justify-between items-center bg-white rounded-xl border border-gray-100 px-4 py-3 text-sm">
                    <span class="text-gray-500">初回購入費</span>
                    <span class="font-medium text-gray-800">{{ $c->initial_purchase_fee ? '¥'.number_format($c->initial_purchase_fee) : '-' }}</span>
                </div>
            @elseif($field->type === 'campaign_recurring_fee')
                <div class="flex justify-between items-center bg-white rounded-xl border border-gray-100 px-4 py-3 text-sm">
                    <span class="text-gray-500">継続購入費</span>
                    <span class="font-medium text-gray-800">{{ $c->recurring_purchase_fee ? '¥'.number_format($c->recurring_purchase_fee) : '-' }}</span>
                </div>
            @elseif($field->type === 'campaign_cooperation_fee')
                <div class="flex justify-between items-center bg-white rounded-xl border border-gray-100 px-4 py-3 text-sm">
                    <span class="text-gray-500">モニター協力金</span>
                    <span class="font-bold text-pink-600 text-base">¥{{ number_format($c->cooperation_fee) }}</span>
                </div>
            @endif
        @endif
    @elseif($field->type === 'image')
        @php $fkey = $field->field_key; @endphp
        <label id="fld-lbl-{{ $fkey }}"
               style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:100%;height:140px;border:2px dashed #d1d5db;border-radius:12px;cursor:pointer;background:#fafafa;">
            <span style="font-size:2rem">📷</span>
            <span style="font-size:13px;color:#9ca3af;margin-top:6px">タップして画像を選択</span>
            <span style="font-size:11px;color:#d1d5db;margin-top:2px">JPG・PNG・WEBP・最大10MB</span>
            <input type="file" name="{{ $inputName }}" accept="image/*" style="display:none"
                   onchange="fldImgPick(this,'{{ $fkey }}')">
        </label>
        <div id="fld-prv-{{ $fkey }}" style="display:none;position:relative;">
            <img id="fld-pim-{{ $fkey }}" src="" alt=""
                 style="width:100%;max-height:240px;object-fit:cover;border-radius:12px;display:block;">
            <button type="button" onclick="fldImgClear('{{ $fkey }}')"
                    style="position:absolute;top:6px;right:6px;width:24px;height:24px;border-radius:50%;background:rgba(0,0,0,.6);color:#fff;border:none;font-size:13px;cursor:pointer;padding:0;line-height:24px;text-align:center;">✕</button>
        </div>
    @endif

    @error($inputName)
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>

@once
<script>
function fldImgPick(input, key) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('fld-pim-' + key).src = e.target.result;
        document.getElementById('fld-lbl-' + key).style.display = 'none';
        document.getElementById('fld-prv-' + key).style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}
function fldImgClear(key) {
    var lbl = document.getElementById('fld-lbl-' + key);
    lbl.querySelector('input[type=file]').value = '';
    document.getElementById('fld-pim-' + key).src = '';
    document.getElementById('fld-prv-' + key).style.display = 'none';
    lbl.style.display = 'flex';
}
</script>
@endonce
