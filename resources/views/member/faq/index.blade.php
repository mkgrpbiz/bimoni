@extends('layouts.member')

@section('title', 'よくある質問')

@section('content')
<div class="py-2">

    <h1 class="font-bold text-gray-700 mb-3">よくある質問</h1>
    <p class="text-xs text-gray-400 mb-4">よくいただくご質問をまとめています。</p>

    @if($categories->isEmpty())
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 text-center">
            <p class="text-xs text-gray-400">質問がありません</p>
        </div>
    @else
        <div x-data="{
                tab: '{{ $categories->first() }}',
                search: '',
                items: @js($items),
                get filtered() {
                    if (this.search.trim() === '') {
                        return this.items.filter(i => i.category === this.tab);
                    }
                    const q = this.search.trim().toLowerCase();
                    return this.items.filter(i => (i.question + i.answer).toLowerCase().includes(q));
                }
            }">

            {{-- キーワード検索 --}}
            <div class="mb-4">
                <input type="text" x-model="search" placeholder="キーワードで検索"
                       class="w-full border border-gray-200 rounded-full px-4 py-2.5 text-sm focus:outline-none focus:border-pink-400">
            </div>

            {{-- カテゴリタブ（検索中は非表示） --}}
            <div x-show="search.trim() === ''" class="flex border-b border-gray-200 mb-4 overflow-x-auto">
                @foreach($categories as $cat)
                <button
                    @click="tab = '{{ $cat }}'"
                    :class="tab === '{{ $cat }}'
                        ? 'border-b-2 border-pink-500 text-pink-600 font-semibold'
                        : 'border-b-2 border-transparent text-gray-500'"
                    class="flex-shrink-0 px-4 py-2.5 text-sm whitespace-nowrap">
                    {{ $cat }}
                </button>
                @endforeach
            </div>

            {{-- Q&A一覧 --}}
            <div class="space-y-3">
                <template x-for="(item, index) in filtered" :key="index">
                    <details class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <summary class="px-4 py-3 flex items-center gap-3 cursor-pointer select-none">
                            <p class="flex-1 text-sm font-medium text-gray-800" x-text="item.question"></p>
                            <span class="text-pink-500 text-xs flex-shrink-0 cancel-label-closed">見る</span>
                            <span class="text-pink-500 text-xs flex-shrink-0 cancel-label-open">閉じる</span>
                        </summary>
                        <div class="px-4 pb-4 pt-1 border-t border-gray-50">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap" x-text="item.answer"></p>
                        </div>
                    </details>
                </template>
                <p x-show="filtered.length === 0" class="text-xs text-gray-400 text-center py-8">
                    該当する質問が見つかりませんでした
                </p>
            </div>

        </div>
    @endif

</div>
<style>
    .cancel-label-open { display: none; }
    details[open] .cancel-label-open { display: inline; }
    details[open] .cancel-label-closed { display: none; }
</style>
@endsection
