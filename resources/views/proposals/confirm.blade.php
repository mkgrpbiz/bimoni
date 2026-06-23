<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>モニター実施のご案内</title>
@vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-sm bg-white rounded-2xl shadow-lg overflow-hidden">

    <div class="bg-pink-600 px-6 py-4">
        <p class="text-white text-xs opacity-80 mb-0.5">BIMONI</p>
        <h1 class="text-white font-bold text-lg">モニター実施のご案内</h1>
    </div>

    <div class="px-6 py-5 space-y-4">

        <div class="space-y-2 text-sm">
            <div class="flex gap-2">
                <span class="text-gray-400 w-24 shrink-0">【商品名】</span>
                <span class="font-medium text-gray-800">{{ $application->campaign->title }}</span>
            </div>
            @if($application->invited_at)
            <div class="flex gap-2">
                <span class="text-gray-400 w-24 shrink-0">【案内日時】</span>
                <span class="font-medium text-gray-800">
                    {{ $application->invited_at->format('m月d日(') }}{{ ['日','月','火','水','木','金','土'][$application->invited_at->dayOfWeek] }}{{ $application->invited_at->format(') H:i') }}
                    @if($application->invited_end_at)
                        〜{{ $application->invited_end_at->format('H:i') }}
                    @endif
                </span>
            </div>
            @endif
            @if($application->campaign->notes)
            <div>
                <span class="text-gray-400 text-xs block mb-1">【注意事項】</span>
                <p class="text-gray-700 text-xs bg-gray-50 rounded p-3 whitespace-pre-line">{{ $application->campaign->notes }}</p>
            </div>
            @endif
        </div>

        <hr class="border-gray-200">

        <p class="text-sm text-gray-700">
            上記の対象時間内にモニター実施（購入/体験）は<strong>可能でしょうか？</strong>
        </p>

        <div class="space-y-3">
            <form method="POST" action="{{ route('proposals.yes', $application->proposal_token) }}">
                @csrf
                <button type="submit"
                        class="w-full bg-pink-600 text-white py-3 rounded-xl font-bold text-base hover:bg-pink-700 active:scale-95 transition-transform">
                    はい、実施できます
                </button>
            </form>

            <a href="{{ route('proposals.no', $application->proposal_token) }}"
               class="block w-full border-2 border-gray-300 text-gray-600 py-3 rounded-xl font-medium text-base text-center hover:border-gray-400 hover:bg-gray-50 transition-colors">
                いいえ、別日程を希望します
            </a>
        </div>

        <p class="text-xs text-gray-400 text-center leading-relaxed">
            限られた予約枠からご案内しています。<br>
            実施できない可能性がある場合は<br>
            【いいえ】から別日程の調整をお願いします。
        </p>
    </div>
</div>
</body>
</html>
