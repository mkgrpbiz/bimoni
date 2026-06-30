@extends('layouts.admin')

@section('title', 'LINE紐付け管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">LINE紐付け管理</h1>
    @if($status === 'transfer')
        <span class="text-sm text-gray-500">紐付け登録: <strong class="text-orange-500">{{ $entries->total() }}人</strong></span>
    @else
        <span class="text-sm text-gray-500">{{ $status === 'linked' ? '紐付け済み' : '未紐付き' }}: <strong class="{{ $status === 'linked' ? 'text-green-600' : 'text-red-500' }}">{{ $unlinked->total() }}人</strong></span>
    @endif
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4 text-sm">{{ $errors->first() }}</div>
@endif

<div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-4 text-sm">
    名前・フリガナ・生年月日・メールのうち3項目以上一致すれば自動紐付けされます。<br>
    自動紐付けできなかった引き継ぎ登録は「紐付け登録」タブ、古いインポートデータで未紐付きのものは「未完了」タブに溜まります。紐付けが完了すると両方のタブから消え、「完了」タブに移動します。
</div>

{{-- タブ --}}
<div class="flex gap-2 mb-4">
    <a href="{{ route('admin.line_links.index', array_merge(request()->except(['page', 'status']), ['status' => 'unlinked'])) }}"
       class="px-4 py-2 rounded text-sm font-medium {{ $status === 'unlinked' ? 'bg-pink-500 text-white' : 'bg-gray-100 text-gray-600' }}">
        未完了（インポートデータ）
    </a>
    <a href="{{ route('admin.line_links.index', array_merge(request()->except(['page', 'status']), ['status' => 'transfer'])) }}"
       class="px-4 py-2 rounded text-sm font-medium {{ $status === 'transfer' ? 'bg-pink-500 text-white' : 'bg-gray-100 text-gray-600' }}">
        紐付け登録（新規登録データ）
    </a>
    <a href="{{ route('admin.line_links.index', array_merge(request()->except(['page', 'status']), ['status' => 'linked'])) }}"
       class="px-4 py-2 rounded text-sm font-medium {{ $status === 'linked' ? 'bg-pink-500 text-white' : 'bg-gray-100 text-gray-600' }}">
        完了
    </a>
</div>

{{-- 検索 --}}
<form method="GET" class="bg-white rounded-lg shadow p-3 mb-4 flex gap-3 items-end">
    <input type="hidden" name="status" value="{{ $status }}">
    <div>
        <label class="block text-xs text-gray-500 mb-1">名前・フリガナ・エルメID検索</label>
        <input type="text" name="name" value="{{ request('name') }}" placeholder="名前・フリガナ・エルメIDで絞り込み"
               class="border rounded px-2 py-1 text-sm w-64">
    </div>
    <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded text-sm hover:bg-pink-600">検索</button>
    <a href="{{ route('admin.line_links.index', ['status' => $status]) }}" class="bg-gray-400 text-white px-4 py-2 rounded text-sm">リセット</a>
</form>

@if($status === 'transfer')
<div class="space-y-3" x-data="importSearchModal()">
    @forelse($entries as $entry)
    @php($user = $entry['user'])
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-start justify-between mb-3">
            <div>
                <div class="font-medium text-gray-800">{{ $user->name }} <span class="text-xs text-gray-400 font-normal">{{ $user->name_kana }}</span></div>
                <div class="text-xs text-gray-500 mt-0.5">
                    {{ $user->birthdate?->format('Y-m-d') ?? '-' }} ／ {{ $user->email ?? '-' }}
                </div>
                <div class="text-xs text-gray-400 mt-0.5">登録日時: {{ $user->transfer_registered_at?->format('Y-m-d H:i') }}</div>
            </div>
            <div class="flex gap-2 shrink-0">
                <button type="button"
                        @click="open({{ $user->id }}, '{{ $user->name }}')"
                        class="bg-pink-500 text-white text-xs px-3 py-1 rounded hover:bg-pink-600">
                    手動検索
                </button>
                <form method="POST" action="{{ route('admin.line_links.confirm_new') }}"
                      onsubmit="return confirm('{{ $user->name }} を正真正銘の新規ユーザーとして確定しますか？このタブから消えます。')">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    <button type="submit" class="bg-gray-400 text-white text-xs px-3 py-1 rounded hover:bg-gray-500">
                        新規として確定
                    </button>
                </form>
            </div>
        </div>

        @if($entry['candidates']->isEmpty())
            <p class="text-sm text-gray-400">一致する古いインポートデータはありません</p>
        @else
            <div class="border-t pt-3 space-y-2">
                @foreach($entry['candidates'] as $cand)
                <div class="flex items-center justify-between bg-gray-50 rounded px-3 py-2">
                    <div class="text-sm">
                        <span class="font-medium text-gray-700">{{ $cand->name }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ $cand->name_kana }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ $cand->birthdate?->format('Y-m-d') ?? '-' }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ $cand->email ?? '-' }}</span>
                        <span class="text-xs text-gray-400 ml-2 font-mono">{{ $cand->erme_respondent_id ?? '-' }}</span>
                        <span class="inline-block text-xs px-2 py-0.5 rounded font-medium ml-2 {{ $cand->match_score >= 2 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $cand->match_score }}項目一致
                        </span>
                    </div>
                    <form method="POST" action="{{ route('admin.line_links.link') }}"
                          onsubmit="return confirm('{{ $user->name }}（新規登録）と {{ $cand->name }}（インポート）を紐付けますか？\n新しい登録データの内容でインポートデータが上書きされます。')">
                        @csrf
                        <input type="hidden" name="import_user_id" value="{{ $cand->id }}">
                        <input type="hidden" name="liff_user_id" value="{{ $user->id }}">
                        <button type="submit" class="bg-green-600 text-white text-xs px-3 py-1 rounded hover:bg-green-700 shrink-0">紐付け確認</button>
                    </form>
                </div>
                @endforeach
            </div>
        @endif
    </div>
    @empty
    <div class="bg-white rounded-lg shadow px-4 py-10 text-center text-gray-400">紐付け登録待ちのユーザーはいません</div>
    @endforelse

    <div class="bg-white rounded-lg shadow px-4 py-3">{{ $entries->links() }}</div>

    {{-- 手動検索モーダル（インポートデータを名前で検索） --}}
    <div x-show="isOpen" x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6" @click.outside="close()">
            <h2 class="font-bold text-gray-800 mb-1">手動紐付け</h2>
            <p class="text-sm text-gray-500 mb-4">新規登録ユーザー: <strong x-text="liffName"></strong></p>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">インポートデータを名前で検索</label>
                <div class="flex gap-2">
                    <input type="text" x-model="searchName" placeholder="名前・フリガナ・エルメID"
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
                一致するインポートデータが見つかりません
            </p>

            <form method="POST" action="{{ route('admin.line_links.link') }}" x-show="selectedUser">
                @csrf
                <input type="hidden" name="liff_user_id" x-bind:value="liffUserId">
                <input type="hidden" name="import_user_id" x-bind:value="selectedUser?.id">
                <div class="bg-green-50 border border-green-200 rounded px-3 py-2 mb-4 text-sm">
                    <span class="text-green-700">選択中: </span>
                    <strong x-text="selectedUser?.name"></strong>
                    <span class="text-gray-500 text-xs ml-2" x-text="selectedUser?.birthdate ?? ''"></span>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            onclick="return confirm('この組み合わせで紐付けますか？新しい登録データの内容でインポートデータが上書きされます。')"
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
@else
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
                <td class="px-4 py-3 text-gray-600">{{ $user->birthdate?->format('Y-m-d') ?? '-' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $user->email ?? '-' }}</td>
                <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $user->erme_respondent_id ?? '-' }}</td>
                <td class="px-4 py-3 text-center">
                    @if($status === 'linked')
                        <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-1 rounded font-medium">紐付け済み</span>
                    @else
                    <div class="flex gap-2 justify-center">
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
                    </div>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                    {{ $status === 'linked' ? '紐付け済みのユーザーはいません' : '未紐付きのユーザーはいません' }}
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
@endif

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

function importSearchModal() {
    return {
        isOpen: false,
        liffUserId: null,
        liffName: '',
        searchName: '',
        results: [],
        searched: false,
        selectedUser: null,

        open(id, name) {
            this.liffUserId = id;
            this.liffName = name;
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
            const res = await fetch(`{{ route('admin.line_links.search_import') }}?name=${encodeURIComponent(this.searchName)}`);
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
