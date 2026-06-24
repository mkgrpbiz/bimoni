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

    <form method="POST" action="{{ route('member.reports.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        {{-- 案件選択 --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                報告する案件 <span class="text-red-500 text-xs">必須</span>
            </label>
            <select name="application_id"
                    onchange="location.href='{{ route('member.reports.create') }}?application_id='+this.value"
                    class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm">
                <option value="">案件を選択してください</option>
                @foreach($applications as $app)
                <option value="{{ $app->id }}" {{ $selectedApp?->id == $app->id ? 'selected' : '' }}>
                    {{ $app->campaign->title }}
                </option>
                @endforeach
            </select>
        </div>

        @if($selectedApp)
        <input type="hidden" name="application_id" value="{{ $selectedApp->id }}">

        {{-- モニター協力金 --}}
        @if($selectedApp->campaign?->cooperation_fee)
        <div class="bg-pink-50 border border-pink-200 rounded-xl px-4 py-3 flex justify-between items-center">
            <span class="text-sm text-gray-600">モニター協力金</span>
            <span class="font-bold text-pink-600 text-base">¥{{ number_format($selectedApp->campaign->cooperation_fee) }}</span>
        </div>
        @endif

        {{-- 動的フィールド --}}
        @foreach($reportFields as $field)
        @include('member._form_field', ['field' => $field])
        @endforeach

        {{-- 画像添付（最大3枚） --}}
        <div>
            <p class="text-sm font-medium text-gray-700 mb-3">
                報告画像 <span class="text-gray-400 text-xs">任意・最大3枚</span>
            </p>

            <div class="grid grid-cols-3 gap-3" id="img-grid">

                <div class="img-slot">
                    <label style="display:flex;flex-direction:column;align-items:center;justify-content:center;aspect-ratio:1/1;border:2px dashed #d1d5db;border-radius:12px;cursor:pointer;overflow:hidden;background:#fafafa;">
                        <span style="font-size:1.4rem">📷</span>
                        <span style="font-size:10px;color:#aaa;margin-top:2px">1枚目</span>
                        <input type="file" name="report_images[]" accept="image/*" style="display:none" onchange="imgPick(this)">
                    </label>
                    <div class="img-preview" style="display:none;position:relative;aspect-ratio:1/1;border-radius:12px;overflow:hidden;">
                        <img style="width:100%;height:100%;object-fit:cover;display:block;">
                        <button type="button" onclick="imgClear(this)" style="position:absolute;top:4px;right:4px;width:20px;height:20px;border-radius:50%;background:rgba(0,0,0,.6);color:#fff;border:none;font-size:10px;cursor:pointer;padding:0;line-height:20px;">✕</button>
                    </div>
                </div>

                <div class="img-slot">
                    <label style="display:flex;flex-direction:column;align-items:center;justify-content:center;aspect-ratio:1/1;border:2px dashed #d1d5db;border-radius:12px;cursor:pointer;overflow:hidden;background:#fafafa;">
                        <span style="font-size:1.4rem">📷</span>
                        <span style="font-size:10px;color:#aaa;margin-top:2px">2枚目</span>
                        <input type="file" name="report_images[]" accept="image/*" style="display:none" onchange="imgPick(this)">
                    </label>
                    <div class="img-preview" style="display:none;position:relative;aspect-ratio:1/1;border-radius:12px;overflow:hidden;">
                        <img style="width:100%;height:100%;object-fit:cover;display:block;">
                        <button type="button" onclick="imgClear(this)" style="position:absolute;top:4px;right:4px;width:20px;height:20px;border-radius:50%;background:rgba(0,0,0,.6);color:#fff;border:none;font-size:10px;cursor:pointer;padding:0;line-height:20px;">✕</button>
                    </div>
                </div>

                <div class="img-slot">
                    <label style="display:flex;flex-direction:column;align-items:center;justify-content:center;aspect-ratio:1/1;border:2px dashed #d1d5db;border-radius:12px;cursor:pointer;overflow:hidden;background:#fafafa;">
                        <span style="font-size:1.4rem">📷</span>
                        <span style="font-size:10px;color:#aaa;margin-top:2px">3枚目</span>
                        <input type="file" name="report_images[]" accept="image/*" style="display:none" onchange="imgPick(this)">
                    </label>
                    <div class="img-preview" style="display:none;position:relative;aspect-ratio:1/1;border-radius:12px;overflow:hidden;">
                        <img style="width:100%;height:100%;object-fit:cover;display:block;">
                        <button type="button" onclick="imgClear(this)" style="position:absolute;top:4px;right:4px;width:20px;height:20px;border-radius:50%;background:rgba(0,0,0,.6);color:#fff;border:none;font-size:10px;cursor:pointer;padding:0;line-height:20px;">✕</button>
                    </div>
                </div>

            </div>
        </div>

        <div class="pb-8">
            <button type="submit"
                    class="w-full bg-pink-500 text-white py-4 rounded-xl font-bold text-base shadow-md hover:bg-pink-600">
                報告を送信する
            </button>
        </div>

        @else
        <div class="bg-gray-50 rounded-xl p-10 text-center text-gray-400 text-sm">
            <p class="text-2xl mb-2">📋</p>
            案件を選択すると報告フォームが表示されます
        </div>
        @endif
    </form>
</div>
@endsection

@push('scripts')
<script>
function imgPick(input) {
    if (!input.files || !input.files[0]) return;
    var slot    = input.closest('.img-slot');
    var label   = slot.querySelector('label');
    var preview = slot.querySelector('.img-preview');
    var img     = preview.querySelector('img');
    var reader  = new FileReader();
    reader.onload = function(e) {
        img.src = e.target.result;
        label.style.display   = 'none';
        preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}

function imgClear(btn) {
    var slot    = btn.closest('.img-slot');
    var label   = slot.querySelector('label');
    var preview = slot.querySelector('.img-preview');
    var img     = preview.querySelector('img');
    var input   = slot.querySelector('input[type=file]');
    img.src               = '';
    input.value           = '';
    preview.style.display = 'none';
    label.style.display   = 'flex';
}
</script>
@endpush
