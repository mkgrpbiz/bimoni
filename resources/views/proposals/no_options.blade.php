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

        @foreach($slots as $slot)
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
</body>
</html>
