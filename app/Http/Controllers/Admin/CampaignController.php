<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Tag;
use App\Services\CampaignClosureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'published');

        $query = Campaign::with('category')->orderBy('sort_order')->orderBy('id')
            ->where('status', $status);

        if ($request->filled('campaign_type')) {
            $query->where('campaign_type', $request->campaign_type);
        }
        if ($request->filled('pr_media')) {
            $query->where('pr_media', $request->pr_media);
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        $campaigns = $query->paginate(50)->withQueryString();

        $statusCounts = Campaign::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('admin.campaigns.index', compact('campaigns', 'status', 'statusCounts'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        return view('admin.campaigns.create', compact('categories', 'tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['created_by'] = Auth::guard('web')->id();

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('campaigns', 'public');
        }
        if ($request->hasFile('monitor_video')) {
            $validated['monitor_video'] = $request->file('monitor_video')->store('campaigns/videos', 'public');
        }
        if ($request->hasFile('monitor_video_thumbnail')) {
            $validated['monitor_video_thumbnail'] = $request->file('monitor_video_thumbnail')->store('campaigns/video_thumbnails', 'public');
        }

        $this->applyCooperationFormula($validated, $request);
        $campaign = Campaign::create($validated);
        $campaign->tags()->sync($validated['tags'] ?? []);

        return redirect()->route('admin.campaigns.index')->with('success', '案件を登録しました。');
    }

    public function show(Campaign $campaign): View
    {
        $campaign->load('category', 'tags');
        return view('admin.campaigns.show', compact('campaign'));
    }

    public function edit(Campaign $campaign): View
    {
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $campaign->load('tags', 'formFields');
        return view('admin.campaigns.edit', compact('campaign', 'categories', 'tags'));
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        if ($request->hasFile('thumbnail')) {
            if ($campaign->thumbnail) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($campaign->thumbnail);
            }
            $validated['thumbnail'] = $request->file('thumbnail')->store('campaigns', 'public');
        }
        if ($request->hasFile('monitor_video')) {
            if ($campaign->monitor_video) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($campaign->monitor_video);
            }
            $validated['monitor_video'] = $request->file('monitor_video')->store('campaigns/videos', 'public');
        }
        if ($request->hasFile('monitor_video_thumbnail')) {
            if ($campaign->monitor_video_thumbnail) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($campaign->monitor_video_thumbnail);
            }
            $validated['monitor_video_thumbnail'] = $request->file('monitor_video_thumbnail')->store('campaigns/video_thumbnails', 'public');
        }

        $validated['capacity'] = $request->filled('capacity') ? (int) $request->capacity : null;
        $this->applyCooperationFormula($validated, $request);

        $campaign->update($validated);
        $campaign->tags()->sync($validated['tags'] ?? []);

        return redirect()->route('admin.campaigns.index')->with('success', '案件を更新しました。');
    }

    public function updateStatus(Request $request, Campaign $campaign, CampaignClosureService $closureService): RedirectResponse
    {
        $request->validate(['status' => 'required|in:published,paused,closed,draft']);

        $wasClosed = $campaign->status === 'closed';
        $campaign->update(['status' => $request->status]);

        if ($request->status === 'closed' && !$wasClosed) {
            $closureService->handleClosure($campaign, Auth::guard('web')->id());
        }

        return back()->with('success', 'ステータスを更新しました。');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->delete();
        return redirect()->route('admin.campaigns.index')->with('success', '案件を削除しました。');
    }

    public function duplicate(Campaign $campaign): RedirectResponse
    {
        $new = $campaign->replicate();
        $new->title      = $campaign->title . '（コピー）';
        $new->status     = 'draft';
        $new->sort_order = Campaign::max('sort_order') + 1;
        $new->save();
        $new->tags()->sync($campaign->tags->pluck('id'));

        return redirect()->route('admin.campaigns.edit', $new)->with('success', '案件を複製しました。');
    }

    public function toggleVisible(Campaign $campaign): RedirectResponse
    {
        $campaign->update(['is_visible' => !$campaign->is_visible]);
        return back()->with('success', '表示設定を変更しました。');
    }

    public function syncFormFields(Request $request, Campaign $campaign): RedirectResponse
    {
        $fieldIds = $request->input('form_field_ids', []);
        $sync = [];
        foreach ($fieldIds as $order => $id) {
            $sync[$id] = ['sort_order' => $order];
        }
        $campaign->formFields()->sync($sync);
        return back()->with('success', '応募フォームフィールドを更新しました。');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        $ids = $validated['ids'];

        foreach ($ids as $order => $id) {
            Campaign::where('id', $id)->update(['sort_order' => $order]);
        }

        // リストにない案件は末尾に
        $offset = count($ids);
        Campaign::whereNotIn('id', $ids)->orderBy('sort_order')->get()
            ->each(fn($c, $i) => $c->update(['sort_order' => $offset + $i]));

        return response()->json(['ok' => true]);
    }

    private function applyCooperationFormula(array &$validated, $request): void
    {
        // 初回協力金: 空欄 or 数値
        $raw = trim($request->input('cooperation_fee', ''));
        if ($raw !== '') {
            $extra = (int) $raw;
            $validated['cooperation_fee']         = $extra;
            $validated['cooperation_fee_formula'] = '初回購入費+' . $extra . '円';
        } else {
            $validated['cooperation_fee']         = null;
            $validated['cooperation_fee_formula'] = null;
        }

        // 継続協力金: 空欄 or 数値
        $rawCont = trim($request->input('continuation_cooperation_fee', ''));
        if ($rawCont !== '') {
            $extraCont = (int) $rawCont;
            $validated['continuation_cooperation_fee']         = $extraCont;
            $validated['continuation_cooperation_fee_formula'] = '継続購入費+' . $extraCont . '円';
        } else {
            $validated['continuation_cooperation_fee']         = null;
            $validated['continuation_cooperation_fee_formula'] = null;
        }

        // 粗利を自動計算して保存
        $initial   = (float) ($validated['initial_purchase_fee']   ?? 0);
        $recurring = (float) ($validated['recurring_purchase_fee'] ?? 0);
        $rate      = (float) ($validated['continuation_rate']      ?? 0);
        $coop      = (float) ($validated['cooperation_fee']        ?? 0);
        $referral  = (float) ($validated['referral_fee']           ?? 0);
        $unitPrice = (float) ($validated['campaign_unit_price']    ?? 0);
        $monitorCost = $initial + $recurring * ($rate / 100) + $coop + $referral;
        $validated['gross_profit'] = (int) round($unitPrice - $monitorCost);
    }

    private function rules(): array
    {
        return [
            'title'                  => 'required|string|max:255',
            'campaign_type'          => 'required|in:experience,product,pr',
            'status'                 => 'required|in:draft,published,paused,closed',
            'category_id'            => 'nullable|exists:categories,id',
            'pr_media'               => 'nullable|in:AD,IF,LINE,monitor',
            'description'            => 'nullable|string',
            'requirements'           => 'nullable|string',
            'notes'                  => 'nullable|string',
            'cancellation_info'      => 'nullable|string',
            'monitor_guide'          => 'nullable|string',
            'link'                   => 'nullable|url|max:500',
            'monitor_invite_message' => 'nullable|string',
            'monitor_end_message'    => 'nullable|string',
            'monitor_video'           => 'nullable|mimes:mp4,mov,avi,webm|max:204800',
            'monitor_video_thumbnail' => 'nullable|image|max:5120',
            'product_name'           => 'nullable|string|max:255',
            'product_price'          => 'nullable|integer|min:0',
            'cooperation_fee'              => 'nullable|integer|min:0',
            'continuation_cooperation_fee' => 'nullable|integer|min:0',
            'referral_fee'           => 'required|integer|in:0,500,1000',
            'campaign_unit_price'    => 'nullable|integer|min:0',
            'initial_purchase_fee'   => 'nullable|integer|min:0',
            'recurring_purchase_fee' => 'nullable|integer|min:0',
            'gross_profit'           => 'nullable|integer',
            'continuation_rate'      => 'nullable|numeric|min:0|max:100',
            'collection_requirement'    => 'nullable|in:回収前提,回収不要',
            'collection_count_judgment' => 'nullable|integer|in:1,2,3',
            'closing_date'           => 'nullable|in:20日,25日,月末',
            'payment_timing'         => 'nullable|in:翌月末,翌々月末',
            'target_gender_ratio'    => 'nullable|string|max:50',
            'target_male_ratio'      => 'nullable|integer|min:0|max:100',
            'target_female_ratio'    => 'nullable|integer|min:0|max:100',
            'capacity'               => 'nullable|integer|min:1',
            'solicitation_target'    => 'nullable|integer|min:0',
            'application_start_at'   => 'nullable|date',
            'application_end_at'     => 'nullable|date|after_or_equal:application_start_at',
            'tags'                   => 'nullable|array',
            'tags.*'                 => 'exists:tags,id',
            'thumbnail'              => 'nullable|image|max:5120',
        ];
    }
}
