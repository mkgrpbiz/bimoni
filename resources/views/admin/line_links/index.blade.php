@extends('layouts.admin')

@section('title', 'LINE紐付け管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">LINE紐付け管理</h1>
    <span class="text-sm text-gray-500">未紐付き: <strong class="text-red-500">{{ $unlinked->total() }}人</strong></span>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4 text-sm">{{ $errors->first() }}</div>
@endif

<div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-4 text-sm">
    インポート済みでまだLINE登録していないユーザーの一覧です。<br>
    ユーザーがLIFF登録すると名前・フリガナ・生年月日で自動紐付けされます。自動紐付けできなかった場合は手動で紐付けてください。
</div>

{{-- 検索 --}}
<form method="GET" class="bg-white rounded-lg shadow p-3 mb-4 flex gap-3 items-end">
    <div>
        <label class="block text-xs text-gray-500 mb-1">名前・フリガナ検索</label>
        <input type="text" name="name" value="{{ request('name') }}" placeholder="名前で絞り込み"
               class="border rounded px-2 py-1 text-sm w-48">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">検索</button>
    <a href="{{ route('admin.line_links.index') }}" class="bg-gray-400 text-white px-4 py-2 rounded text-sm">リセット</a>
</form>

<div class="bg-white rounded-lg shadow overflow-hidden" x-data="linkModal()">

    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-700">
            <tr>
                <th class="px-4 py-3 text-left">名前</th>
                <th class="px-4 py-3 text-left">フリガナ</th>
                <th class="px-4 py-3 text-left">性別</th>
                <th class="px-4 py-3 text-left">生年月日</th>
                <th class="px-4 py-3 text-left">メール</th>
                <th class="px-4 py-3 text-left">エルメID</th>
                <th class="px-4 py-3 text-center">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($unlinked as $user)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $user->name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $user->name_kana }}</td>
                <td class="px-4 py-3 text-gray-600">
                    {{ $user->gender === 'male' ? '男性' : ($user->gender === 'female' ? '女性' : '-') }}
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $user->birthdate ?? '-' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $user->email ?? '-' }}</td>
                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $user->erme_respondent_id ?? '-' }}</td>
                <td class="px-4 py-3 text-center flex gap-2 justify-center">
                    <button type="button"
                            @click="open({{ $user->id }}, '{{ $user->name }}')"
                            class="bg-pink-500 text-white text-xs px-3 py-1 rounded hover:bg-pink-600">
                        手動紐付け
                    </button>
                    <form method="POST" action="{{ route('admin.line_links.skip') }}"
                          onsubmit="return confirm('{{ $user->name }} をスキップしますか？一覧から非表示になります。')">
                        @csrf
                        <input type="hidden" name="import_user_id" value="{{ $user->id }}">
                        <button type="submit" class="bg-gray-400 text-white text-xs px-3 py-1 rounded hover:bg-gray-500">
                            スキップ
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                    未紐付きのユーザーはいません
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="px-4 py-3 border-t">{{ $unlinked->links() }}</div>

    {{-- 手動紐付けモーダル --}}
    <div x-show="isOpen" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6" @click.outside="close()">
            <h2 class="font-bold text-gray-800 mb-1">手動紐付け</h2>
            <p class="text-sm text-gray-500 mb-4">インポートユーザー: <strong x-text="importName"></strong></p>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">LINE登録済みユーザーを名前で検索</label>
                <div class="flex gap-2">
                    <input type="text" x-model="searchName" placeholder="名前・フリガナ"
                           class="border rounded px-3 py-2 text-sm flex-1">
                    <button type="button" @click="search()"
                            class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">検索</button>
                </div>
            </div>

            <div x-show="results.length > 0" class="border rounded mb-4 divide-y max-h-52 overflow-y-auto">
                <template x-for="u in results" :key="u.id">
                    <div class="px-3 py-2 flex items-center justify-between hover:bg-gray-50">
                        <div>
                            <span class="font-medium text-sm" x-text="u.name"></span>
                            <span class="text-xs text-gray-400 ml-2" x-text="u.name_kana"></span>
                            <span class="text-xs text-gray-400 ml-2" x-text="u.birthdate ?? ''"></span>
                        </div>
                        <button type="button" @click="selectUser(u)"
                                class="bg-green-600 text-white text-xs px-3 py-1 rounded hover:bg-green-700">選択</button>
                    </div>
                </template>
            </div>
            <p x-show="searched && results.length === 0" class="text-sm text-gray-400 mb-4">
                一致するLINEユーザーが見つかりません
            </p>

            <form method="POST" action="{{ route('admin.line_links.link') }}" x-show="selectedUser">
                @csrf
                <input type="hidden" name="import_user_id" x-bind:value="importUserId">
                <input type="hidden" name="liff_user_id" x-bind:value="selectedUser?.id">
                <div class="bg-green-50 border border-green-200 rounded px-3 py-2 mb-4 text-sm">
                    <span class="text-green-700">選択中: </span>
                    <strong x-text="selectedUser?.name"></strong>
                    <span class="text-gray-500 text-xs ml-2" x-text="selectedUser?.birthdate ?? ''"></span>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            onclick="return confirm('この組み合わせで紐付けますか？LINEユーザーの応募・報告データも移動されます。')"
                            class="bg-green-600 text-white px-5 py-2 rounded text-sm hover:bg-green-700">紐付け実行</button>
                    <button type="button" @click="close()" class="bg-gray-400 text-white px-5 py-2 rounded text-sm">キャンセル</button>
                </div>
            </form>

            <div x-show="!selectedUser" class="mt-2">
                <button type="button" @click="close()" class="bg-gray-400 text-white px-5 py-2 rounded text-sm">閉じる</button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function linkModal() {
    return {
        isOpen: false,
        importUserId: null,
        importName: '',
        searchName: '',
        results: [],
        searched: false,
        selectedUser: null,

        open(id, name) {
            this.importUserId = id;
            this.importName = name;
            this.searchName = name;
            this.results = [];
            this.searched = false;
            this.selectedUser = null;
            this.isOpen = true;
            this.$nextTick(() => this.search());
        },

        close() {
            this.isOpen = false;
        },

        async search() {
            if (!this.searchName.trim()) return;
            const res = await fetch(`{{ route('admin.line_links.search') }}?name=${encodeURIComponent(this.searchName)}`);
            const data = await res.json();
            this.results = data;
            this.searched = true;
            this.selectedUser = null;
        },

        selectUser(u) {
            this.selectedUser = u;
            this.results = [];
        }
    }
}
</script>
@endpush
@endsection
