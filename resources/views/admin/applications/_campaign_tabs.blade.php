{{--
  $allCampaigns    : Campaign コレクション
  $activeCampaignId: 現在選択中の campaign ID（null = すべて）
--}}
@php
    $grouped = $allCampaigns->groupBy('status');
    $statusLabels = ['draft' => '下書き', 'published' => '公開中', 'paused' => '一時停止', 'closed' => '終了'];
    // アクティブ案件のステータスを特定（初期表示フィルターに使う）
    $activeStatus = $activeCampaignId
        ? ($allCampaigns->firstWhere('id', $activeCampaignId)?->status ?? 'published')
        : 'published';
@endphp

<div class="mb-4">
    {{-- ステータスフィルターボタン --}}
    <div class="flex gap-1 mb-2" id="status-filter-btns">
        @foreach($statusLabels as $status => $label)
            @if($grouped->has($status))
            <button
                onclick="filterTabs('{{ $status }}')"
                id="btn-{{ $status }}"
                class="px-3 py-1 rounded text-xs font-medium transition-colors
                       {{ $status === 'published' ? 'bg-green-500 text-white' : '' }}
                       {{ $status === 'draft'     ? 'bg-yellow-500 text-white' : '' }}
                       {{ $status === 'paused'    ? 'bg-orange-500 text-white' : '' }}
                       {{ $status === 'closed'    ? 'bg-gray-500 text-white' : '' }}
                       opacity-40 hover:opacity-100">
                {{ $label }}（{{ $grouped->get($status)->count() }}）
            </button>
            @endif
        @endforeach
    </div>

    {{-- 案件タブ（ステータスごとに div でグループ分け） --}}
    <div class="border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
        <div class="flex gap-0 min-w-max" id="tab-container">
            {{-- 「すべて」タブ --}}
            <a href="{{ route('admin.applications.index') }}"
               data-status="all"
               class="tab-item px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                      {{ $activeCampaignId === null
                          ? 'border-pink-500 text-pink-600 dark:text-pink-400'
                          : 'border-transparent text-gray-700 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                すべて
            </a>

            @foreach($statusLabels as $status => $label)
                @foreach($grouped->get($status, collect()) as $c)
                <a href="{{ route('admin.campaigns.applications', $c) }}"
                   data-status="{{ $status }}"
                   class="tab-item px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                          {{ $activeCampaignId === $c->id
                              ? 'border-pink-500 text-pink-600 dark:text-pink-400'
                              : 'border-transparent text-gray-700 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}">
                    {{ $c->title }}
                </a>
                @endforeach
            @endforeach
        </div>
    </div>
</div>

<script>
(function () {
    const activeStatus = '{{ $activeStatus }}';

    function filterTabs(status) {
        // タブの表示切り替え
        document.querySelectorAll('.tab-item[data-status]').forEach(function (el) {
            const s = el.getAttribute('data-status');
            el.style.display = (s === 'all' || s === status) ? '' : 'none';
        });
        // ボタンの強調
        document.querySelectorAll('#status-filter-btns button').forEach(function (btn) {
            btn.style.opacity = btn.id === 'btn-' + status ? '1' : '0.4';
        });
        localStorage.setItem('appTabStatus', status);
    }

    // 初期表示: アクティブ案件のステータスを優先、なければlocalStorageまたはpublished
    const saved = '{{ $activeCampaignId }}' !== '' ? activeStatus
                : (localStorage.getItem('appTabStatus') || 'published');
    filterTabs(saved);
})();
</script>

