{{--
  $allCampaigns    : Campaign コレクション
  $activeCampaignId: 現在選択中の campaign ID（null = すべて）
  $currentStatus   : 現在選択中の案件ステータス
--}}
@php
    $grouped = $allCampaigns->groupBy('status');
    $statusLabels = ['published' => '公開中', 'paused' => '一時停止', 'closed' => '終了', 'draft' => '下書き'];
    $currentStatus ??= $allCampaigns->firstWhere('id', $activeCampaignId)?->status ?? 'published';
@endphp

<div class="border-b border-gray-200 overflow-x-auto mb-4">
    <div class="flex gap-0 min-w-max" id="tab-container">
        {{-- 「すべて」タブ --}}
        <a href="{{ route('admin.applications.index', ['status' => $currentStatus]) }}"
           data-status="{{ $currentStatus }}"
           class="tab-item px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                  {{ $activeCampaignId === null
                      ? 'border-pink-500 text-pink-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            すべて
        </a>

        @foreach($grouped->get($currentStatus, collect()) as $c)
        <a href="{{ route('admin.campaigns.applications', $c) }}"
           data-status="{{ $c->status }}"
           class="tab-item px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                  {{ $activeCampaignId === $c->id
                      ? 'border-pink-500 text-pink-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            {{ $c->title }}
        </a>
        @endforeach
    </div>
</div>
