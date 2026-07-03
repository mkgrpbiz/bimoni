@extends('layouts.admin')

@section('title', 'データインポート')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">データインポート</h1>

@if(session('error'))
    <div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-4 py-2 rounded mb-4 text-sm">{{ session('error') }}</div>
@endif

@if(session('import_result'))
    @php $r = session('import_result'); @endphp
    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-4">
        <p class="font-bold text-blue-800 dark:text-blue-300 mb-2">{{ session('import_type') }}インポート結果</p>
        <p class="text-sm text-blue-700 dark:text-blue-300">✓ 成功：{{ $r['success'] }}件　スキップ：{{ $r['skipped'] }}件</p>
        @if(!empty($r['errors']))
            <div class="mt-2">
                <p class="text-sm font-medium text-red-600 dark:text-red-400">エラー（{{ count($r['errors']) }}件）</p>
                <ul class="text-xs text-red-500 dark:text-red-400 mt-1 space-y-0.5 max-h-32 overflow-y-auto">
                    @foreach($r['errors'] as $err)
                        <li>・{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif

<div class="space-y-4">

    {{-- 案件インポート --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-1">案件インポート</h2>
        <div class="text-xs text-gray-500 dark:text-gray-400 mb-3 space-y-0.5">
            <p class="font-medium text-gray-700 dark:text-gray-300">Googleスプレッドシートから「ファイル → ダウンロード → CSV」でそのまま出力してインポートできます。</p>
            <p>・ヘッダー行：<code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">全否認, 案件名, シート名, ステータス, PR媒体, 開始, 終了, 締め日, 支払日, 報酬単価, 初回, 継続, 協力金, 紹介単価, 継続率, 粗利, 男性比, 女性比, モニター注意事項</code></p>
            <p>・ステータス：実施中 / 募集中 / 一時停止 / 終了 / 準備中　をそのまま使えます</p>
            <p>・PR媒体：AD / LINE / Instagram　をそのまま使えます</p>
            <p>・金額の¥・カンマ、比率の%は自動で除去します</p>
            <p>・全否認がTRUEの案件は非表示で登録されます</p>
            <p>・同一案件名は重複スキップされます</p>
        </div>
        <form method="POST" action="{{ route('admin.import.campaigns') }}" enctype="multipart/form-data" class="flex gap-3 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CSVファイル</label>
                <input type="file" name="csv_file" accept=".csv,.txt" required
                       class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <button type="submit"
                    onclick="return confirm('案件CSVをインポートしますか？')"
                    class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 text-sm">
                インポート実行
            </button>
        </form>
    </div>

    {{-- ユーザーインポート --}}
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 mb-1">ユーザーインポート</h2>
        <div class="text-xs text-gray-500 mb-3 space-y-0.5">
            <p>CSVヘッダー（日本語列名も対応）：<code class="bg-gray-100 px-1 rounded">回答者ID, 回答者名（任意）, 名前, フリガナ, 性別, 生年月日, 紹介コード, メールアドレス</code></p>
            <p>・英語ヘッダーも可：<code class="bg-gray-100 px-1 rounded">erme_respondent_id, name, name_kana, gender, birthdate, referred_by_code, email</code></p>
            <p>・性別：男性/女性 または male/female</p>
            <p>・CSVに<code class="bg-gray-100 px-1 rounded">紹介コード</code>列がある場合はその値を使用。代理店を選択すると未登録コードは自動発行されます</p>
            <p>・代理店のコードが1つだけの場合、CSV列が空の行にもそのコードが適用されます</p>
            <p>・エルメID・メールアドレスが重複する行はスキップされます</p>
        </div>
        <form method="POST" action="{{ route('admin.import.users') }}" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">代理店（一括適用・CSVコード自動発行先）</label>
                <select name="agent_id" required class="border rounded px-3 py-1.5 text-sm w-72">
                    <option value="">選択してください</option>
                    @foreach($parentAgents as $parent)
                        <optgroup label="{{ $parent->name }}">
                            <option value="{{ $parent->id }}">{{ $parent->name }}（親）</option>
                            @foreach($parent->children as $child)
                                <option value="{{ $child->id }}">　└ {{ $child->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CSVファイル <span class="text-red-500">*</span></label>
                <input type="file" name="csv_file" accept=".csv,.txt" required
                       class="border rounded px-3 py-1.5 text-sm">
            </div>
            <button type="submit"
                    onclick="return confirm('ユーザーCSVをインポートしますか？')"
                    class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 text-sm">
                インポート実行
            </button>
        </form>
    </div>

    {{-- 応募リストインポート --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 dark:text-gray-200 mb-1">応募リストインポート</h2>
        <div class="text-xs text-gray-500 dark:text-gray-400 mb-3 space-y-0.5">
            <p class="font-medium text-gray-700 dark:text-gray-300">エルメの応募フォーム回答データをそのままCSVでインポートできます。案件を選択してください。</p>
            <p>・ヘッダー行（日本語）に対応：<code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">回答日時, 回答者ID, 回答者, お名前(漢字フルネーム), フリガナ, 生年月日をご入力ください, 性別を選択してください, 購入可能時間を選択して下さい, 継続購入がある場合〜, ステータス, 採用日, 継続</code></p>
            <p>・ステータス：実施完了 / キャンセル / 予約中 / 実施確認中 / 打診中 / 空欄（応募のみ）</p>
            <p>・継続購入希望（はい/いいえ）と継続打診承諾（TRUE/FALSE）を自動マッピング</p>
            <p>・採用日が入力されているとステータスが実施完了の場合に <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">completed_at</code> に設定されます</p>
            <p>・回答者IDでユーザーを検索。見つからない場合は新規ユーザーとして登録します</p>
            <p>・重複チェック：同一ユーザー×同一案件×同一応募日時の場合はスキップ</p>
        </div>
        <form method="POST" action="{{ route('admin.import.applications') }}" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">案件 <span class="text-red-500">*</span></label>
                <select name="campaign_id" required class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-1.5 text-sm w-64">
                    <option value="">選択してください</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}">{{ $campaign->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CSVファイル <span class="text-red-500">*</span></label>
                <input type="file" name="csv_file" accept=".csv,.txt" required
                       class="border dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded px-3 py-1.5 text-sm">
            </div>
            <button type="submit"
                    onclick="return confirm('応募リストCSVをインポートしますか？')"
                    class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 text-sm">
                インポート実行
            </button>
        </form>
    </div>

    {{-- 報告インポート --}}
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 mb-1">報告インポート</h2>
        <div class="text-xs text-gray-500 mb-3 space-y-0.5">
            <p>CSVヘッダー：<code class="bg-gray-100 px-1 rounded">報告日時, 回答者ID, 回答者名（任意）, 名前, フリガナ, 案件名, 初回か継続, モニター経費, キャンペーン</code></p>
            <p>・報告日時が空欄の場合はインポート実行時の日時が使われます</p>
            <p>・初回か継続：<code class="bg-gray-100 px-1 rounded">初回</code> または <code class="bg-gray-100 px-1 rounded">継続</code></p>
            <p>・ステータスは承認済みで登録されます</p>
            <p>・回答者IDでユーザーを検索、なければ名前+フリガナで検索します（新規作成はしません）</p>
            <p>・同一ユーザー×案件×初回/継続が重複する行はスキップされます</p>
            <p>・モニター経費の¥・カンマは自動除去します</p>
            <p>・キャンペーン列に何か書いてあれば+300円のボーナスを設定、空欄はボーナスなし</p>
        </div>
        <form method="POST" action="{{ route('admin.import.reports') }}" enctype="multipart/form-data" class="flex gap-3 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CSVファイル <span class="text-red-500">*</span></label>
                <input type="file" name="csv_file" accept=".csv,.txt" required
                       class="border rounded px-3 py-1.5 text-sm">
            </div>
            <button type="submit"
                    onclick="return confirm('報告CSVをインポートしますか？')"
                    class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 text-sm">
                インポート実行
            </button>
        </form>
    </div>


    {{-- 回収インポート --}}
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="font-bold text-gray-700 mb-1">回収インポート</h2>
        <div class="text-xs text-gray-500 mb-3 space-y-0.5">
            <p>CSVヘッダー：<code class="bg-gray-100 px-1 rounded">報告日時, 回答者ID, 回答者名, 名前, フリガナ, 商品数, 送料, 追跡番号</code></p>
            <p>・報告日時が空欄の場合はインポート実行時の日時が使われます</p>
            <p>・追跡番号が重複する行はスキップされます</p>
            <p>・回答者IDでユーザーを検索、なければ名前+フリガナで検索します（新規作成はしません）</p>
            <p>・協力金は自動計算（800円×商品数、4個以下は送料を差し引き）</p>
            <p>・ステータスは「承認済み」で登録されます</p>
            <p>・送料の¥・カンマは自動除去します</p>
        </div>
        <form method="POST" action="{{ route('admin.import.collections') }}" enctype="multipart/form-data" class="flex gap-3 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CSVファイル <span class="text-red-500">*</span></label>
                <input type="file" name="csv_file" accept=".csv,.txt" required
                       class="border rounded px-3 py-1.5 text-sm">
            </div>
            <button type="submit"
                    onclick="return confirm('回収CSVをインポートしますか？')"
                    class="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 text-sm">
                インポート実行
            </button>
        </form>
    </div>

</div>
@endsection

