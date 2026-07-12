@extends('layouts.member')

@section('title', $page->title)

@section('content')
<div class="py-2">

    @if($page->hero_image)
    <div class="rounded-2xl overflow-hidden mb-4 shadow-sm">
        <img src="{{ asset('storage/' . $page->hero_image) }}" alt="{{ $page->title }}" class="w-full">
    </div>
    @endif

    <h1 class="font-bold text-gray-700 mb-4 text-lg">{{ $page->title }}</h1>

    @if($page->cta_label && $page->cta_url)
    <a href="{{ $page->cta_url }}"
       class="block w-full text-center bg-gradient-to-r from-pink-500 to-pink-400 text-white font-bold py-4 rounded-full shadow-md mb-5">
        {{ $page->cta_label }}
    </a>
    @endif

    <div class="space-y-5">
        @foreach($page->sections as $section)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <h2 class="font-bold text-pink-500 text-xl mb-2">{{ $section->title }}</h2>
            <div class="h-0.5 bg-gradient-to-r from-pink-400 to-transparent rounded-full mb-3"></div>

            @if($section->intro_text)
            <p class="text-sm text-gray-700 whitespace-pre-wrap mb-3">{{ $section->intro_text }}</p>
            @endif

            @foreach($section->notes as $note)
            <div class="rounded-xl p-3 mb-3 text-sm font-medium text-black border
                        {{ match($note->style) {
                            'red'    => 'bg-red-50 border-red-100',
                            'orange' => 'bg-orange-50 border-orange-100',
                            default  => 'bg-pink-50 border-pink-100',
                        } }}">
                @if($note->heading)
                <p class="font-bold text-base mb-1">{{ $note->heading }}</p>
                @endif
                <ul class="list-disc list-inside space-y-1">
                    @foreach($note->lines as $line)
                    <li>{{ $line }}</li>
                    @endforeach
                </ul>
            </div>
            @endforeach

            @if($section->steps->isNotEmpty())
            <div class="space-y-4 mt-2">
                @foreach($section->steps as $i => $step)
                <div class="flex gap-3 {{ !$loop->first ? 'pt-4 border-t border-dashed border-gray-200' : '' }}">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-pink-500 to-pink-400 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                        {{ $i + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm text-gray-800">{{ $step->title }}</p>
                        @if($step->description)
                        <p class="text-sm text-gray-600 whitespace-pre-wrap mt-0.5">{{ $step->description }}</p>
                        @endif
                        @if($step->sub_text)
                        <p class="text-xs text-gray-400 mt-1">※ {{ $step->sub_text }}</p>
                        @endif
                        @if($step->image)
                        <img src="{{ asset('storage/' . $step->image) }}" alt="{{ $step->title }}" class="mt-2 rounded-lg border border-gray-100">
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>

</div>
@endsection
