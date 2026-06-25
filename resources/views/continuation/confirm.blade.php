<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>継続購入のご案内</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
<div class="bg-white rounded-2xl shadow-md w-full max-w-sm p-6">
    <div class="text-center mb-6">
        <p class="text-xs text-gray-400 mb-1">継続購入のご案内</p>
        <h1 class="text-base font-bold text-gray-800">{{ $application->campaign->title }}</h1>
    </div>

    <p class="text-sm text-gray-600 mb-6 leading-relaxed">
        継続購入についてのご希望をお聞かせください。
    </p>

    <div class="space-y-3">
        <form method="POST" action="{{ route('continuation.accept', $application->continuation_token) }}">
            @csrf
            <button type="submit"
                    class="w-full bg-pink-500 text-white py-3 rounded-xl font-bold text-sm hover:bg-pink-600 active:bg-pink-700">
                継続購入可能
            </button>
        </form>

        <form method="POST" action="{{ route('continuation.decline', $application->continuation_token) }}">
            @csrf
            <button type="submit"
                    class="w-full bg-gray-200 text-gray-700 py-3 rounded-xl font-bold text-sm hover:bg-gray-300 active:bg-gray-400">
                継続購入不可
            </button>
        </form>
    </div>
</div>
</body>
</html>
