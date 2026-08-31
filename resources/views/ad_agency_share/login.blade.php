<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BIMONI 代理店共有 - ログイン</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm">
        <h1 class="text-center font-bold text-xl text-pink-700 mb-6">BIMONI 代理店共有</h1>

        <form method="POST" action="{{ route('ad_agency_share.login') }}"
              class="bg-white rounded-lg shadow p-6 space-y-4">
            @csrf

            @error('email')
                <p class="text-red-500 text-xs">{{ $message }}</p>
            @enderror

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full border rounded px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">パスワード</label>
                <input type="password" name="password" required
                       class="w-full border rounded px-3 py-2 text-sm">
            </div>

            <button type="submit" class="w-full bg-pink-500 text-white py-2 rounded hover:bg-pink-600 text-sm font-medium">
                ログイン
            </button>
        </form>
    </div>
</body>
</html>
