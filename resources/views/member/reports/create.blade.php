@extends('layouts.member')
@section('title', 'モニター報告')
@section('content')
<div class="py-4">
    <div class="flex items-center gap-2 mb-4">
        <a href="{{ route('member.mypage') }}" class="text-pink-500 text-sm">← マイページ</a>
    </div>
    <h1 class="font-bold text-gray-700 mb-3">モニター報告</h1>
    <p class="text-xs text-gray-400 mb-4">実施が完了したモニター報告をお願いいたします。</p>

    @if(session('error'))
        <div class="bg-red-100 text-red-800 rounded-xl px-4 py-3 text-sm mb-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm mb-4 text-red-700">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    @php $oldMode = old('purchase_type', 'initial') === 'continuation' ? 'continuation' : 'initial'; @endphp

    <form method="POST" action="{{ route('member.reports.store') }}"
          enctype="multipart/form-data" class="space-y-5">
        @csrf
        <input type="hidden" name="purchase_type" id="monitor-purchase-type" value="{{ $oldMode }}">
        <input type="hidden" name="application_id" id="monitor-application-id" value="{{ old('application_id') }}">

        {{-- 購入区分 --}}
        <div x-data="{ mode: '{{ $oldMode }}' }">
            <p class="text-sm font-medium text-gray-700 mb-2">購入区分を選択してください</p>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <label :class="mode === 'initial' ? 'border-pink-500 bg-pink-50' : 'border-gray-200 bg-white'"
                       class="border-2 rounded-xl p-3 cursor-pointer text-center transition-all">
                    <input type="radio" name="mode_radio" value="initial" x-model="mode"
                           onchange="onModeChange('initial')" class="hidden">
                    <div class="font-bold text-sm text-gray-800">初回購入分</div>
                </label>
                <label :class="mode === 'continuation' ? 'border-pink-500 bg-pink-50' : 'border-gray-200 bg-white'"
                       class="border-2 rounded-xl p-3 cursor-pointer text-center transition-all">
                    <input type="radio" name="mode_radio" value="continuation" x-model="mode"
                           onchange="onModeChange('continuation')" class="hidden">
                    <div class="font-bold text-sm text-gray-800">継続購入分</div>
                </label>
            </div>

            <div x-show="mode === 'initial'" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    対象案件 <span class="text-red-500 text-xs">必須</span>
                </label>
                <select id="monitor-initial-select" onchange="onMonitorSelectChange(this)"
                        class="w-full border border-gray-300 rounded-xl px-3 py-3 text-sm">
                    <option value="">選択してください</option>
                    @foreach($monitorInitialApps as $app)
                    <option value="{{ $app->id }}"
                            data-fee="{{ $app->campaign->cooperation_fee ?? 0 }}"
                            data-bonus="{{ $app->bonus_amount ?? 0 }}"
                            {{ (string) old('application_id') === (string) $app->id ? 'selected' : '' }}>
                        {{ $app->campaign->title }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div x-show="mode === 'continuation'" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    対象案件 <span class="text-red-500 text-xs">必須</span>
                </label>
                <select id="monitor-cont-select" onchange="onMonitorSelectChange(this)"
                        class="w-full border border-gray-300 rounded-xl px-3 py-3 text-sm">
                    <option value="">選択してください</option>
                    @foreach($monitorContinuationApps as $app)
                    <option value="{{ $app->id }}"
                            data-fee="{{ $app->campaign->continuation_cooperation_fee ?? 0 }}"
                            data-bonus="{{ $app->bonus_amount ?? 0 }}"
                            {{ (string) old('application_id') === (string) $app->id ? 'selected' : '' }}>
                        {{ $app->campaign->title }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- モニター経費 --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                モニター経費 <span class="text-red-500 text-xs">必須</span>
            </label>
            <p class="text-xs text-gray-500 mb-2 leading-relaxed">
                商品代金・送料・返送費など実際にかかった費用の合計を記入してください。<br>
                ※体験モニターは【 ０ 】となります。
            </p>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">¥</span>
                <input type="number" name="purchase_amount" id="purchase-amount-input"
                       inputmode="numeric" min="0"
                       value="{{ old('purchase_amount', 0) }}" required
                       oninput="recalcFee()"
                       class="w-full border border-gray-300 rounded-xl pl-7 pr-3 py-3 text-sm">
            </div>
        </div>

        {{-- モニター協力金（自動計算） --}}
        <div class="bg-pink-50 border border-pink-200 rounded-xl px-4 py-4 space-y-2" id="monitor-fee-box">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600">モニター経費</span>
                <span class="font-medium text-gray-800" id="display-expense">0円</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600">＋ モニター協力金</span>
                <span class="font-medium text-gray-800" id="display-extra">-</span>
            </div>
            <div class="flex justify-between items-center text-sm" id="display-bonus-row" style="display:none!important">
                <span class="text-gray-600">＋ キャンペーン</span>
                <span class="font-medium text-red-500" id="display-bonus">0円</span>
            </div>
            <div class="border-t border-pink-200 pt-2 flex justify-between items-center">
                <span class="text-sm font-bold text-gray-700">合計（モニター協力金）</span>
                <span class="font-bold text-pink-600 text-lg" id="monitor-fee-display">-</span>
            </div>
        </div>

        {{-- 支払い方法 --}}
        <div>
            <p class="text-sm font-medium text-gray-700 mb-1">
                商品購入時のお支払い方法 <span class="text-red-500 text-xs">必須</span>
            </p>
            <p class="text-xs text-gray-500 mb-2">
                メーカー都合によってお支払い方法が変更された場合、変更後のお支払い方法を選択してください。
            </p>
            <div class="space-y-2" x-data="{ payMethod: '{{ old('payment_method') }}' }">
                @foreach([
                    'credit_card' => 'クレジットカード',
                    'cod'         => '代引き',
                    'deferred'    => '後払い',
                    'bank'        => '銀行振込',
                    'none'        => 'お支払い無し',
                    'other'       => 'その他',
                ] as $val => $lbl)
                <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3 cursor-pointer">
                    <input type="radio" name="payment_method" value="{{ $val }}" required
                           @click="payMethod = '{{ $val }}'"
                           {{ old('payment_method') === $val ? 'checked' : '' }}
                           class="text-pink-500">
                    <span class="text-sm">{{ $lbl }}</span>
                </label>
                @endforeach
                <div x-show="payMethod === 'other'" x-cloak class="pl-4">
                    <input type="text" name="payment_method_other"
                           value="{{ old('payment_method_other') }}"
                           placeholder="お支払い方法を入力"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        {{-- 画像1: 商品受取確認 --}}
        <div>
            <p class="text-sm font-medium text-gray-700 mb-1">
                商品の受け取りが確認できる画像 <span class="text-red-500 text-xs">必須</span>
            </p>
            <p class="text-xs text-gray-500 mb-2">
                ※商品現物、購入者名、商品名、金額が確認できる画像<br>
                ※同封されている【明細書】も写るように撮影してください。<br>
                ※クレカ払いの場合は【明細書】が添付されない商品もありますので、その場合は商品のみでお願いします。<br>
                ※体験モニターの場合は実施完了が確認できる画面
            </p>
            @include('member._image_picker', ['inputName' => 'report_image_1', 'labelText' => '1枚目', 'required' => true, 'pickerId' => 'rimg1'])
        </div>

        {{-- 画像2: 支払い確認 --}}
        <div>
            <p class="text-sm font-medium text-gray-700 mb-1">
                支払い完了が確認できる画像 <span class="text-red-500 text-xs">必須</span>
            </p>
            <p class="text-xs text-gray-500 mb-2">
                ※購入日・購入者名・金額・支払い方法がわかる画像でお願いします。<br>
                ※体験モニターの場合は完了画面など（1枚目と同じ画像でOK）
            </p>
            @include('member._image_picker', ['inputName' => 'report_image_2', 'labelText' => '2枚目', 'required' => true, 'pickerId' => 'rimg2'])
        </div>

        {{-- 画像3: 任意（返送・解約時） --}}
        <div>
            <p class="text-sm font-medium text-gray-700 mb-1">
                その他の添付画像 <span class="text-gray-400 text-xs">任意</span>
            </p>
            <p class="text-xs text-gray-500 mb-2">
                解約時に商品返送が必要な場合は発送伝票の控えと返送料が確認できる写真を添付してください。
            </p>
            @include('member._image_picker', ['inputName' => 'report_image_3', 'labelText' => '3枚目（任意）', 'required' => false, 'pickerId' => 'rimg3'])
        </div>

        <div class="pb-2">
            <button type="submit"
                    class="w-full bg-pink-500 text-white py-4 rounded-xl font-bold text-base shadow-md hover:bg-pink-600">
                報告する
            </button>
            <p class="text-xs text-gray-400 text-center mt-2">※報告確認後、問題がなければモニター協力金に反映されます。</p>
        </div>
    </form>

    {{-- ======== 常時表示: その他報告 ======== --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4 mt-6">
        <p class="text-sm font-bold text-gray-700 mb-1">上記に該当する案件がない場合</p>
        <p class="text-xs text-gray-400 mb-3">案件一覧に見つからないイレギュラーな内容は、こちらから報告してください。</p>
        <form method="POST" action="{{ route('member.reports.store') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="hidden" name="purchase_type" value="other">
            <textarea name="report_body" rows="4" required placeholder="報告内容を詳しく記入してください"
                      class="w-full border border-gray-300 rounded-xl px-3 py-3 text-sm leading-relaxed">{{ old('report_body') }}</textarea>
            @include('member._image_picker', ['inputName' => 'report_image_1', 'labelText' => '画像（必須）', 'required' => true, 'pickerId' => 'other_rimg1'])
            @include('member._image_picker', ['inputName' => 'report_image_2', 'labelText' => '画像2（任意）', 'required' => false, 'pickerId' => 'other_rimg2'])
            <button type="submit"
                    class="w-full bg-gray-600 text-white py-3 rounded-xl font-bold text-sm hover:bg-gray-700">
                その他報告を送信する
            </button>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
function currentMode() {
    const checked = document.querySelector('input[name="mode_radio"]:checked');
    return checked ? checked.value : 'initial';
}

function onModeChange(mode) {
    document.getElementById('monitor-purchase-type').value = mode;
    document.getElementById('monitor-application-id').value = '';
    resetFeeDisplay();
}

function onMonitorSelectChange(sel) {
    document.getElementById('monitor-application-id').value = sel.value;
    recalcFee();
}

function resetFeeDisplay() {
    document.getElementById('display-expense').textContent = '0円';
    document.getElementById('display-extra').textContent = '-';
    document.getElementById('monitor-fee-display').textContent = '-';
    document.getElementById('display-bonus-row').style.setProperty('display', 'none', 'important');
}

function recalcFee() {
    const mode  = currentMode();
    const selId = mode === 'initial' ? 'monitor-initial-select' : 'monitor-cont-select';
    const sel   = document.getElementById(selId);
    if (!sel || !sel.value) { resetFeeDisplay(); return; }

    const opt = sel.options[sel.selectedIndex];
    const extraBonus    = parseInt(opt.dataset.fee || 0);
    const campaignBonus = parseInt(opt.dataset.bonus || 0);
    const purchaseAmt   = parseInt(document.getElementById('purchase-amount-input')?.value || 0);
    const totalFee      = purchaseAmt + extraBonus + campaignBonus;

    document.getElementById('display-expense').textContent = purchaseAmt.toLocaleString() + '円';
    const extraRow = document.getElementById('display-extra').closest('.flex');
    document.getElementById('display-extra').textContent = '+' + extraBonus.toLocaleString() + '円';
    if (extraRow) extraRow.style.setProperty('display', extraBonus > 0 ? '' : 'none', 'important');
    const bonusRow = document.getElementById('display-bonus-row');
    if (campaignBonus > 0) {
        document.getElementById('display-bonus').textContent = '+' + campaignBonus.toLocaleString() + '円';
        bonusRow.style.removeProperty('display');
    } else {
        bonusRow.style.setProperty('display', 'none', 'important');
    }
    document.getElementById('monitor-fee-display').textContent = totalFee.toLocaleString() + '円';
}

document.addEventListener('DOMContentLoaded', function() {
    recalcFee();
});
</script>
@endpush
