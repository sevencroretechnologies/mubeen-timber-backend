<?php

namespace App\Services\crm;

use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Prospect;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class LeadService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Lead::with(['leadOwner', 'notes']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['qualification_status'])) {
            $query->where('qualification_status', $filters['qualification_status']);
        }
        if (!empty($filters['lead_owner_id'])) {
            $query->where('lead_owner_id', $filters['lead_owner_id']);
        }
        if (!empty($filters['territory'])) {
            $query->where('territory', $filters['territory']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('lead_name', 'like', "%{$search}%")
                    ->orWhere('email_id', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('mobile_no', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function find(int $id): Lead
    {
        return Lead::with(['leadOwner', 'qualifiedByUser', 'prospects', 'notes'])->findOrFail($id);
    }

    public function create(array $data): Lead
    {
        if (!empty($data['email_id'])) {
            $this->validateUniqueEmail($data['email_id']);
        }

        return Lead::create($data);
    }

    public function update(int $id, array $data): Lead
    {
        $lead = Lead::findOrFail($id);

        if (!empty($data['email_id']) && $data['email_id'] !== $lead->email_id) {
            $this->validateUniqueEmail($data['email_id'], $id);
        }

        $lead->update($data);
        return $lead->fresh(['leadOwner', 'qualifiedByUser', 'prospects', 'notes']);
    }

    public function delete(int $id): bool
    {
        $lead = Lead::findOrFail($id);
        $lead->prospects()->detach();
        return $lead->delete();
    }

    public function convertToOpportunity(int $leadId, array $data = []): Opportunity
    {
        $lead = Lead::findOrFail($leadId);

        $opportunityData = array_merge([
            'opportunity_from' => 'Lead',
            'party_id' => $lead->id,
            'customer_name' => $lead->company_name ?: $lead->lead_name,
            'contact_email' => $lead->email_id,
            'contact_mobile' => $lead->mobile_no,
            'territory' => $lead->territory,
            'industry' => $lead->industry,
            'market_segment' => $lead->market_segment,
            'website' => $lead->website,
            'city' => $lead->city,
            'state' => $lead->state,
            'country' => $lead->country,
            'company' => $lead->company,
            'transaction_date' => now()->toDateString(),
            'status' => 'Open',
        ], $data);

        $opportunity = Opportunity::create($opportunityData);

        $lead->update(['status' => 'Opportunity']);

        return $opportunity;
    }

    public function addToProspect(int $leadId, int $prospectId): void
    {
        $lead = Lead::findOrFail($leadId);
        $prospect = Prospect::findOrFail($prospectId);

        $prospect->leads()->syncWithoutDetaching([
            $lead->id => [
                'lead_name' => $lead->lead_name,
                'email' => $lead->email_id,
                'mobile_no' => $lead->mobile_no,
                'status' => $lead->status,
            ]
        ]);
    }

    public function createProspect(int $leadId, string $prospectName = null): Prospect
    {
        $lead = Lead::findOrFail($leadId);

        $prospect = Prospect::create([
            'company_name' => $prospectName ?: $lead->company_name,
            'no_of_employees' => $lead->no_of_employees,
            'industry' => $lead->industry,
            'market_segment' => $lead->market_segment,
            'annual_revenue' => $lead->annual_revenue,
            'territory' => $lead->territory,
            'fax' => $lead->fax,
            'website' => $lead->website,
            'prospect_owner_id' => $lead->lead_owner_id,
            'company' => $lead->company,
        ]);

        $prospect->leads()->attach($lead->id, [
            'lead_name' => $lead->lead_name,
            'email' => $lead->email_id,
            'mobile_no' => $lead->mobile_no,
            'status' => $lead->status,
        ]);

        return $prospect;
    }

    private function validateUniqueEmail(string $email, int $excludeId = null): void
    {
        $query = Lead::where('email_id', $email);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        if ($query->exists()) {
            throw new \InvalidArgumentException('A lead with this email already exists.');
        }
    }
}
