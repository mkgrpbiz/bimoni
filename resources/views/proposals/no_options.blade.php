<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>別日程を選択</title>
@vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-sm bg-white rounded-2xl shadow-lg overflow-hidden">

    <div class="bg-indigo-600 px-6 py-4">
        <p class="text-white text-xs opacity-80 mb-0.5">BIMONI</p>
        <h1 class="text-white font-bold text-lg">別日程を選択してください</h1>
    </div>

    <div class="px-6 py-5 space-y-3">

        @if(isset($prDeadline) && $prDeadline)
        {{-- PR打診/通常打診 タブ --}}
        <div class="flex border-b border-gray-200 mb-1 -mx-1">
            <button id="tab-pr" onclick="showProposalTab('pr')"
                    class="flex-1 py-2 text-sm font-medium border-b-2 border-pink-500 text-pink-600">
                PR打診
            </button>
            <button id="tab-normal" onclick="showProposalTab('normal')"
                    class="flex-1 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                通常打診
            </button>
        </div>

        {{-- PR打診タブコンテンツ --}}
        <div id="content-pr">
            <div class="bg-pink-50 border border-pink-200 rounded-xl px-4 py-3 text-sm">
                <p class="text-xs text-pink-400 mb-1">【PR実施期限】</p>
                <p class="font-bold text-pink-700 text-base">
                    今から {{ $prDeadline->format('m月d日(') }}{{ ['日','月','火','水','木','金','土'][$prDeadline->dayOfWeek] }}{{ $prDeadline->format(') H:i') }} まで
                </p>
                <p class="text-xs text-pink-400 mt-1">今すぐ実施可能な場合は下のボタンを押してください。案内をすぐにお送りします。</p>
            </div>
            <form method="POST" action="{{ route('proposals.pr_now', $application->proposal_token) }}" class="mt-3">
                @csrf
                <button type="submit"
                        class="w-full bg-pink-600 text-white py-3 rounded-xl font-bold text-base hover:bg-pink-700 active:scale-95 transition-transform">
                    今すぐ実施します
                </button>
            </form>
        </div>

        {{-- 通常打診タブコンテンツ --}}
        <div id="content-normal" style="display:none">
        @endif

        @if(isset($minStart))
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-2 text-xs text-amber-700">
            他のモニター終了後48時間の制限により、<strong>{{ $minStart->format('m/d H:i') }}以降</strong>の日程を表示しています。
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-2 text-xs text-red-700">
            {{ session('error') }}
        </div>
        @endif

        <p class="text-sm text-gray-600">実施可能な日程をお選びください。</p>

        @php $prevDate = null; @endphp
        @foreach($slots as $slot)
            @php $slotDate = \Carbon\Carbon::parse($slot['start'])->format('m/d'); @endphp
            @if($slotDate !== $prevDate)
                @if($prevDate !== null)<div class="pt-1"></div>@endif
                <p class="text-xs font-semibold text-gray-500 pt-1">{{ $slotDate }}</p>
                @php $prevDate = $slotDate; @endphp
            @endif
            <form method="POST" action="{{ route('proposals.slot', $application->proposal_token) }}">
                @csrf
                <input type="hidden" name="slot_start" value="{{ $slot['start'] }}">
                <input type="hidden" name="slot_end" value="{{ $slot['end'] }}">
                <button type="submit"
                        class="w-full border-2 border-indigo-300 text-indigo-700 py-3 rounded-xl font-medium text-sm hover:bg-indigo-50 active:scale-95 transition-all">
                    {{ $slot['label'] }}
                </button>
            </form>
        @endforeach

        @if(empty($slots))
        <p class="text-sm text-gray-400 text-center py-4">現在選択可能な日程がありません。</p>
        @endif

        @if(isset($prDeadline) && $prDeadline)
        </div>{{-- #content-normal 終わり --}}
        @endif

        <hr class="border-gray-200 my-2">

        <form method="POST" action="{{ route('proposals.cancel', $application->proposal_token) }}">
            @csrf
            <button type="submit"
                    class="w-full border border-red-200 text-red-400 py-2.5 rounded-xl text-sm hover:bg-red-50 transition-colors"
                    onclick="return confirm('キャンセルすると再応募が必要になります。よろしいですか？')">
                キャンセル【再応募が必要になります】
            </button>
        </form>

        <a href="{{ route('proposals.confirm', $application->proposal_token) }}"
           class="block text-center text-xs text-gray-400 hover:underline mt-2">
            ← 戻る
        </a>
    </div>
</div>

@if(isset($prDeadline) && $prDeadline)
<script>
function showProposalTab(tab) {
    var isPr = tab === 'pr';
    document.getElementById('content-pr').style.display = isPr ? '' : 'none';
    document.getElementById('content-normal').style.display = isPr ? 'none' : '';
    document.getElementById('tab-pr').className = isPr
        ? 'flex-1 py-2 text-sm font-medium border-b-2 border-pink-500 text-pink-600'
        : 'flex-1 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700';
    document.getElementById('tab-normal').className = isPr
        ? 'flex-1 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700'
        : 'flex-1 py-2 text-sm font-medium border-b-2 border-indigo-500 text-indigo-600';
}
</script>
@endif

</body>
</html>
