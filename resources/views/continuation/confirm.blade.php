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

    <p class="text-xs text-gray-400 text-center mb-1">継続購入のご案内</p>
    <h1 class="text-base font-bold text-gray-800 text-center mb-5">{{ $application->campaign->title }}</h1>

    {{-- 回収サービス情報 --}}
    @if($application->campaign->collection_requirement)
    <div class="bg-gray-50 rounded-xl border border-gray-200 px-4 py-3 mb-5 space-y-2 text-sm">
        <div class="flex justify-between">
            <span class="text-gray-500">回収サービス</span>
            <span class="font-medium text-gray-800">{{ $application->campaign->collection_requirement }}</span>
        </div>
        @if($application->campaign->collection_requirement === '回収前提' && $application->campaign->collection_count_judgment)
        @php $collectionFee = 800 * $application->campaign->collection_count_judgment; @endphp
        <div class="flex justify-between">
            <span class="text-gray-500">回収個数</span>
            <span class="font-medium text-gray-800">{{ $application->campaign->collection_count_judgment }}個</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500">回収時の金額</span>
            <span class="font-medium text-gray-800">800×{{ $application->campaign->collection_count_judgment }}＝{{ number_format($collectionFee) }}円</span>
        </div>
        @endif
    </div>
    @endif

    <p class="text-sm text-gray-700 font-medium text-center mb-6">
        2回目の継続購入は可能でしょうか？
    </p>

    <div class="space-y-3">
        <form method="POST" action="{{ route('continuation.accept', $application->continuation_token) }}">
            @csrf
            <button type="submit"
                    class="w-full bg-pink-500 text-white py-3 rounded-xl font-bold text-sm hover:bg-pink-600 active:bg-pink-700">
                継続購入可能です。
            </button>
        </form>

        <form method="POST" action="{{ route('continuation.decline', $application->continuation_token) }}">
            @csrf
            <button type="submit"
                    class="w-full bg-gray-200 text-gray-700 py-3 rounded-xl font-bold text-sm hover:bg-gray-300 active:bg-gray-400">
                継続購入不可です。
            </button>
        </form>
    </div>
</div>
</body>
</html>
