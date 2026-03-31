<?php

namespace App\Services\crm;

use App\Models\Campaign;
use Illuminate\Pagination\LengthAwarePaginator;

class CampaignService
{
    /**
     * List all campaigns with filtering and searching.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Campaign::query();

        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('campaign_code', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Find a campaign by ID.
     */
    public function find(int $id): Campaign
    {
        return Campaign::findOrFail($id);
    }

    /**
     * Create a new campaign.
     */
    public function create(array $data): Campaign
    {
        return Campaign::create($data);
    }

    /**
     * Update an existing campaign.
     */
    public function update(int $id, array $data): Campaign
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->update($data);
        return $campaign;
    }

    /**
     * Delete a campaign.
     */
    public function delete(int $id): bool
    {
        $campaign = Campaign::findOrFail($id);
        return $campaign->delete();
    }
}
