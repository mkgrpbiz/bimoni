@extends('layouts.admin')

@section('title', '案件編集')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.campaigns.index') }}" class="text-gray-400 hover:text-gray-600">← 一覧に戻る</a>
    <h1 class="text-2xl font-bold text-gray-800">案件編集</h1>
</div>

<form method="POST" action="{{ route('admin.campaigns.update', $campaign) }}">
    @csrf @method('PATCH')
    @include('admin.campaigns._form')
</form>
@endsection
