<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $query = Campaign::with('category')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('campaign_type')) {
            $query->where('campaign_type', $request->campaign_type);
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        $campaigns = $query->paginate(20)->withQueryString();

        return view('admin.campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        return view('admin.campaigns.create', compact('categories', 'tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'                  => 'required|string|max:255',
            'campaign_type'          => 'required|in:experience,product,recovery',
            'status'                 => 'required|in:draft,published,closed',
            'category_id'            => 'nullable|exists:categories,id',
            'pr_media'               => 'nullable|string|max:255',
            'description'            => 'nullable|string',
            'requirements'           => 'nullable|string',
            'notes'                   => 'nullable|string',
            'monitor_invite_message'  => 'nullable|string',
            'monitor_end_message'     => 'nullable|string',
            'product_name'            => 'nullable|string|max:255',
            'product_price'           => 'nullable|integer|min:0',
            'cooperation_fee'         => 'required|integer|min:0',
            'referral_fee'            => 'required|integer|min:0',
            'campaign_unit_price'     => 'nullable|integer|min:0',
            'initial_purchase_fee'    => 'nullable|integer|min:0',
            'recurring_purchase_fee'  => 'nullable|integer|min:0',
            'gross_profit'            => 'nullable|integer',
            'continuation_rate'       => 'nullable|numeric|min:0|max:100',
            'target_gender_ratio'     => 'nullable|string|max:50',
            'target_male_ratio'       => 'nullable|integer|min:0|max:100',
            'target_female_ratio'     => 'nullable|integer|min:0|max:100',
            'capacity'                => 'required|integer|min:1',
            'solicitation_target'     => 'nullable|integer|min:0',
            'application_start_at'    => 'nullable|date',
            'application_end_at'      => 'nullable|date|after_or_equal:application_start_at',
            'tags'                    => 'nullable|array',
            'tags.*'                  => 'exists:tags,id',
        ]);

        $validated['created_by'] = Auth::guard('web')->id();

        $campaign = Campaign::create($validated);

        if (!empty($validated['tags'])) {
            $campaign->tags()->sync($validated['tags']);
        }

        return redirect()->route('admin.campaigns.index')
            ->with('success', '案件を登録しました。');
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
        $campaign->load('tags');
        return view('admin.campaigns.edit', compact('campaign', 'categories', 'tags'));
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $validated = $request->validate([
            'title'                  => 'required|string|max:255',
            'campaign_type'          => 'required|in:experience,product,recovery',
            'status'                 => 'required|in:draft,published,closed',
            'category_id'            => 'nullable|exists:categories,id',
            'pr_media'               => 'nullable|string|max:255',
            'description'            => 'nullable|string',
            'requirements'           => 'nullable|string',
            'notes'                   => 'nullable|string',
            'monitor_invite_message'  => 'nullable|string',
            'monitor_end_message'     => 'nullable|string',
            'product_name'            => 'nullable|string|max:255',
            'product_price'           => 'nullable|integer|min:0',
            'cooperation_fee'         => 'required|integer|min:0',
            'referral_fee'            => 'required|integer|min:0',
            'campaign_unit_price'     => 'nullable|integer|min:0',
            'initial_purchase_fee'    => 'nullable|integer|min:0',
            'recurring_purchase_fee'  => 'nullable|integer|min:0',
            'gross_profit'            => 'nullable|integer',
            'continuation_rate'       => 'nullable|numeric|min:0|max:100',
            'target_gender_ratio'     => 'nullable|string|max:50',
            'target_male_ratio'       => 'nullable|integer|min:0|max:100',
            'target_female_ratio'     => 'nullable|integer|min:0|max:100',
            'capacity'                => 'required|integer|min:1',
            'solicitation_target'     => 'nullable|integer|min:0',
            'application_start_at'    => 'nullable|date',
            'application_end_at'      => 'nullable|date|after_or_equal:application_start_at',
            'tags'                    => 'nullable|array',
            'tags.*'                  => 'exists:tags,id',
        ]);

        $campaign->update($validated);
        $campaign->tags()->sync($validated['tags'] ?? []);

        return redirect()->route('admin.campaigns.index')
            ->with('success', '案件を更新しました。');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->delete();
        return redirect()->route('admin.campaigns.index')
            ->with('success', '案件を削除しました。');
    }
}
