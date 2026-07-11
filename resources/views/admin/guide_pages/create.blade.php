@extends('layouts.admin')

@section('title', 'ガイドページ新規作成')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.guide_pages.index') }}"
       class="bg-pink-500 text-white px-3 py-1.5 rounded hover:bg-pink-600 text-sm">← 一覧に戻る</a>
    <h1 class="text-2xl font-bold text-gray-800">ガイドページ新規作成</h1>
</div>

<form method="POST" action="{{ route('admin.guide_pages.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.guide_pages._form')
</form>
@endsection
