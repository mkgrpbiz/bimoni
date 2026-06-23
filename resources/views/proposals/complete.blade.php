<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>予約受付完了</title>
@vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-sm bg-white rounded-2xl shadow-lg overflow-hidden">

    <div class="bg-green-500 px-6 py-4">
        <p class="text-white text-xs opacity-80 mb-0.5">BIMONI</p>
        <h1 class="text-white font-bold text-lg">予約を受け付けました</h1>
    </div>

    <div class="px-6 py-5 space-y-4">

        <div class="bg-green-50 rounded-xl p-4 text-sm text-green-800">
            <p>ありがとうございます。</p>
            <p class="mt-2">設定された案内時間になりましたら、<br>モニター実施案内をお送りします。</p>
            @if($application->invited_at)
            <p class="mt-2 font-medium">
                案内予定: {{ $application->invited_at->format('m/d H:i') }}
            </p>
            @endif
        </div>

        <hr class="border-gray-200">

        <p class="text-xs text-gray-500 text-center">
            間違えて押された場合は必ず<br>
            下の【間違えた】を押してください
        </p>

        <form method="POST" action="{{ route('proposals.revert', $application->proposal_token) }}">
            @csrf
            <button type="submit"
                    class="w-full border border-gray-300 text-gray-500 py-2.5 rounded-xl text-sm hover:bg-gray-50 transition-colors"
                    onclick="return confirm('予約をキャンセルして打診中に戻しますか？')">
                間違えた
            </button>
        </form>
    </div>
</div>
</body>
</html>
