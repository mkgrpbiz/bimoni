@extends('layouts.portal')
@section('title', '設定')
@section('content')
<h1 class="text-xl font-bold text-gray-800 mb-4">情報変更</h1>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-lg shadow p-6 max-w-md">
    <form method="POST" action="{{ route('portal.settings.update') }}">
        @csrf @method('PATCH')
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">代理店名</label>
            <input type="text" name="name" value="{{ old('name', $agent->name) }}" required
                   class="w-full border rounded px-3 py-2 text-sm @error('name') border-red-400 @enderror">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">ポータルURL（変更不可）</label>
            <code class="block text-xs bg-gray-100 px-3 py-2 rounded break-all text-gray-600">{{ $agent->portalUrl() }}</code>
        </div>
        <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded text-sm hover:bg-gray-700">更新する</button>
    </form>
</div>
@endsection
