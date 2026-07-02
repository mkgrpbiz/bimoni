@extends('layouts.member')
@section('title', 'モニター報告')
@section('content')
<div class="py-4">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('member.mypage') }}" class="text-gray-500 text-sm">← マイページ</a>
        <h1 class="text-lg font-bold text-gray-800">モニター報告</h1>
    </div>

    @if(session('error'))
        <div class="bg-red-100 text-red-800 rounded-xl px-4 py-3 text-sm mb-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm mb-4 text-red-700">
            @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
        </div>
    @endif

    {{-- 報告種別選択 --}}
    <div x-data="{ reportType: '{{ old('report_type', $reportType ?? 'monitor') }}' }">

        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-5">
            <p class="text-sm font-medium text-gray-700 mb-3">報告の種類を選択してください</p>
            <div class="grid grid-cols-2 gap-3">
                <label :class="reportType === 'monitor' ? 'border-pink-500 bg-pink-50' : 'border-gray-200 bg-white'"
                       class="border-2 rounded-xl p-4 cursor-pointer text-center transition-all">
                    <input type="radio" name="report_type_select" value="monitor" x-model="reportType" class="hidden">
                    <div class="text-2xl mb-1">📋</div>
                    <div class="font-bold text-sm text-gray-800">モニター報告</div>
                    <div class="text-xs text-gray-500 mt-1">購入を報告する</div>
                </label>
                <label :class="reportType === 'collection' ? 'border-pink-500 bg-pink-50' : 'border-gray-200 bg-white'"
                       class="border-2 rounded-xl p-4 cursor-pointer text-center transition-all">
                    <input type="radio" name="report_type_select" value="collection" x-model="reportType" class="hidden">
                    <div class="text-2xl mb-1">📦</div>
                    <div class="font-bold text-sm text-gray-800">回収サービス</div>
                    <div class="text-xs text-gray-500 mt-1">商品を返送する</div>
                </label>
            </div>
        </div>

        {{-- ======== 回収サービスフォーム ======== --}}
        <div x-show="reportType === 'collection'" x-cloak>
            <form method="POST" action="{{ route('member.reports.store_collection') }}"
                  enctype="multipart/form-data" class="space-y-5">
                @csrf
                <input type="hidden" name="report_type" value="collection">

                {{-- 対象案件（複数選択） --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        返送する商品の案件を選択 <span class="text-red-500 text-xs">必須</span>
                    </label>
                    <p class="text-xs text-amber-600 mb-2">※5つ以下は送料がご負担になります。</p>

                    @error('initial_app_ids')
                        <p class="text-red-500 text-xs mb-2">{{ $message }}</p>
                    @enderror

                    {{-- 初回分 --}}
                    @if($initialApplications->isNotEmpty())
                    <p class="text-xs font-medium text-gray-500 mb-1 mt-1">初回分</p>
                    <div class="space-y-2">
                        @foreach($initialApplications as $app)
                        <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3 cursor-pointer">
                            <input type="checkbox" name="initial_app_ids[]" value="{{ $app->id }}"
                                   data-fee="800"
                                   class="rounded border-gray-300 text-pink-500"
                                   onchange="updateCollectionFee()">
                            <span class="text-sm text-gray-800">{{ $app->campaign->title }}</span>
                        </label>
                        @endforeach
                    </div>
                    @endif

                    {{-- 継続分 --}}
                    @if($continuationApplications->isNotEmpty())
                    <p class="text-xs font-medium text-gray-500 mb-1 mt-3">継続分</p>
                    <div class="space-y-2">
                        @foreach($continuationApplications as $app)
                        @php
                            $contCount = $app->campaign->collection_count_judgment ?? 1;
                            $contFee   = 800 * $contCount;
                        @endphp
                        <label class="flex items-center gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3 cursor-pointer">
                            <input type="checkbox" name="continuation_app_ids[]" value="{{ $app->id }}"
                                   data-fee="{{ $contFee }}"
                                   class="rounded border-gray-300 text-pink-500"
                                   onchange="updateCollectionFee()">
                            <span class="text-sm text-gray-800">{{ $app->campaign->title }}（継続分）@if($contCount >= 2)×{{ $contCount }}@endif
                            </span>
                        </label>
                        @endforeach
                    </div>
                    @endif

                    {{-- 送料 --}}
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            送料 <span class="text-red-500 text-xs">必須</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">¥</span>
                            <input type="number" name="shipping_fee" inputmode="numeric" min="0"
                                   value="{{ old('shipping_fee', 0) }}" required
                                   onchange="updateCollectionFee()" id="shipping-fee-input"
                                   class="w-full border border-gray-300 rounded-xl pl-7 pr-3 py-3 text-sm">
                        </div>
                    </div>

                    {{-- 回収サービス協力金表示 --}}
                    <div class="bg-pink-50 border border-pink-200 rounded-xl px-4 py-3 mt-3" id="collection-fee-box">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">回収サービス協力金</span>
                            <span class="font-bold text-pink-600 text-base" id="collection-fee-display">0円</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1" id="collection-fee-note"></p>
                    </div>
                </div>

                {{-- 画像1: 段ボール --}}
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        段ボールを閉じる前の写真 <span class="text-red-500 text-xs">必須</span>
                    </p>
                    <p class="text-xs text-gray-500 mb-2">段ボールを閉じる前の状態が確認できる写真を添付してください。</p>
                    @include('member._image_picker', ['inputName' => 'box_image', 'labelText' => '写真を選択', 'required' => true, 'pickerId' => 'box_image'])
                </div>

                {{-- 到着予定日 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        到着予定日 <span class="text-red-500 text-xs">必須</span>
                    </label>
                    <select name="estimated_arrival_date" required
                            class="w-full border border-gray-300 rounded-xl px-3 py-3 text-sm">
                        <option value="">選択してください</option>
                        @for($i = 1; $i <= 14; $i++)
                        @php $d = now()->addDays($i); @endphp
                        <option value="{{ $d->format('Y-m-d') }}" {{ old('estimated_arrival_date') === $d->format('Y-m-d') ? 'selected' : '' }}>
                            {{ $d->format('m月d日（') }}{{ ['日','月','火','水','木','金','土'][$d->dayOfWeek] }}{{ ')' }}
                        </option>
                        @endfor
                    </select>
                </div>

                {{-- 追跡番号 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        追跡番号 <span class="text-red-500 text-xs">必須</span>
                    </label>
                    <p class="text-xs text-gray-500 mb-1">数字のみ入力してください（ハイフン不要）</p>
                    <input type="text" name="tracking_number" inputmode="numeric" pattern="[0-9]+"
                           value="{{ old('tracking_number') }}" required
                           placeholder="1234567890123"
                           class="w-full border border-gray-300 rounded-xl px-3 py-3 text-sm">
                </div>

                {{-- 画像2: 伝票 --}}
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-1">
                        発送伝票の写真 <span class="text-red-500 text-xs">必須</span>
                    </p>
                    <p class="text-xs text-gray-500 mb-2">追跡番号と送料が確認できるよう、発送伝票の控えの写真を添付してください。</p>
                    @include('member._image_picker', ['inputName' => 'label_image', 'labelText' => '写真を選択', 'required' => true, 'pickerId' => 'label_image'])
                </div>

                <div class="pb-8">
                    <button type="submit"
                            class="w-full bg-pink-500 text-white py-4 rounded-xl font-bold text-base shadow-md hover:bg-pink-600">
                        報告する
                    </button>
                    <p class="text-xs text-gray-400 text-center mt-2">※商品確認後、問題がなければモニター協力金に反映されます。</p>
                </div>
            </form>
        </div>

        {{-- ======== モニター報告フォーム ======== --}}
        <div x-show="reportType === 'monitor'" x-cloak>
            <form method="POST" action="{{ route('member.reports.store') }}"
                  enctype="multipart/form-data" class="space-y-5">
                @csrf
                <input type="hidden" name="report_type" value="monitor">

                <input type="hidden" name="purchase_type" id="monitor-purchase-type">
                <input type="hidden" name="application_id" id="monitor-application-id">

                {{-- 初回購入 --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        初回購入 <span class="text-red-500 text-xs">必須（どちらか選択）</span>
                    </label>
                    <select id="monitor-initial-select"
                            onchange="onMonitorSelectChange(this, 'initial')"
                            class="w-full border border-gray-300 rounded-xl px-3 py-3 text-sm">
                        <option value="">選択してください</option>
                        @foreach($monitorInitialApps as $app)
                        <option value="{{ $app->id }}"
                                data-fee="{{ $app->campaign->cooperation_fee ?? 0 }}"
                                data-bonus="{{ $app->bonus_amount ?? 0 }}">
                            {{ $app->campaign->title }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- 継続購入 --}}
                @if($monitorContinuationApps->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">継続購入</label>
                    <select id="monitor-cont-select"
                            onchange="onMonitorSelectChange(this, 'continuation')"
                            class="w-full border border-gray-300 rounded-xl px-3 py-3 text-sm">
                        <option value="">選択してください</option>
                        @foreach($monitorContinuationApps as $app)
                        <option value="{{ $app->id }}"
                                data-fee="{{ $app->campaign->cooperation_fee ?? 0 }}"
                                data-bonus="{{ $app->bonus_amount ?? 0 }}">
                            {{ $app->campaign->title }}（継続分）
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

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
                               oninput="refreshMonitorFee()"
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

                <div class="pb-8">
                    <button type="submit"
                            class="w-full bg-pink-500 text-white py-4 rounded-xl font-bold text-base shadow-md hover:bg-pink-600">
                        報告する
                    </button>
                    <p class="text-xs text-gray-400 text-center mt-2">※報告確認後、問題がなければモニター協力金に反映されます。</p>
                </div>
            </form>
        </div>

    </div>{{-- x-data end --}}
</div>
@endsection

@push('scripts')
<script>
function updateCollectionFee() {
    const checked = [
        ...document.querySelectorAll('input[name="initial_app_ids[]"]:checked'),
        ...document.querySelectorAll('input[name="continuation_app_ids[]"]:checked'),
    ];
    const count = checked.length;
    const shippingFee = parseInt(document.getElementById('shipping-fee-input')?.value || 0);

    let gross = 0;
    checked.forEach(cb => { gross += parseInt(cb.dataset.fee || 800); });

    const fee = count <= 5 ? gross - shippingFee : gross;

    document.getElementById('collection-fee-display').textContent = fee.toLocaleString() + '円';

    if (count > 0 && count <= 5) {
        document.getElementById('collection-fee-note').textContent =
            gross.toLocaleString() + '円 - 送料' + shippingFee.toLocaleString() + '円 = ' + fee.toLocaleString() + '円';
    } else if (count > 0) {
        document.getElementById('collection-fee-note').textContent = gross.toLocaleString() + '円';
    } else {
        document.getElementById('collection-fee-note').textContent = '';
    }
}

function onMonitorSelectChange(sel, purchaseType) {
    if (!sel.value) return;
    // 他方をリセット
    const otherId = purchaseType === 'initial' ? 'monitor-cont-select' : 'monitor-initial-select';
    const other = document.getElementById(otherId);
    if (other) other.value = '';

    document.getElementById('monitor-purchase-type').value  = purchaseType;
    document.getElementById('monitor-application-id').value = sel.value;

    const opt = sel.options[sel.selectedIndex];
    const extraBonus  = parseInt(opt.dataset.fee || 0);
    const campaignBonus = parseInt(opt.dataset.bonus || 0);
    const purchaseAmt = parseInt(document.getElementById('purchase-amount-input')?.value || 0);
    const totalFee    = purchaseAmt + extraBonus + campaignBonus;

    document.getElementById('display-expense').textContent = purchaseAmt.toLocaleString() + '円';
    document.getElementById('display-extra').textContent   = '+' + extraBonus.toLocaleString() + '円';
    const bonusRow = document.getElementById('display-bonus-row');
    if (campaignBonus > 0) {
        document.getElementById('display-bonus').textContent = '+' + campaignBonus.toLocaleString() + '円';
        bonusRow.style.removeProperty('display');
    } else {
        bonusRow.style.setProperty('display', 'none', 'important');
    }
    document.getElementById('monitor-fee-display').textContent = totalFee.toLocaleString() + '円';
}

function refreshMonitorFee() {
    const initialSel = document.getElementById('monitor-initial-select');
    const contSel    = document.getElementById('monitor-cont-select');
    if (initialSel && initialSel.value) onMonitorSelectChange(initialSel, 'initial');
    else if (contSel && contSel.value)  onMonitorSelectChange(contSel, 'continuation');
}

document.addEventListener('DOMContentLoaded', function() {
    updateCollectionFee();
});
</script>
@endpush
