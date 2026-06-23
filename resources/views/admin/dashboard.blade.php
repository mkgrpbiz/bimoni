@extends('layouts.admin')

@section('title', 'ダッシュボード')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-6">ダッシュボード</h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 dark:text-gray-400">ユーザー数</p>
        <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">-</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 dark:text-gray-400">公開中の案件</p>
        <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">-</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 dark:text-gray-400">今月の応募数</p>
        <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">-</p>
    </div>
</div>
@endsection
