<?php

namespace App\Services\crm;

use App\Models\Contract;
use App\Models\ContractFulfilmentChecklist;
use Illuminate\Pagination\LengthAwarePaginator;

class ContractService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Contract::with(['partyUser', 'fulfilmentChecklists']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['party_type'])) {
            $query->where('party_type', $filters['party_type']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('party_name', 'like', "%{$search}%")
                    ->orWhere('contract_terms', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function find(int $id): Contract
    {
        return Contract::with(['partyUser', 'fulfilmentChecklists'])->findOrFail($id);
    }

    public function create(array $data): Contract
    {
        $checklists = $data['fulfilment_checklists'] ?? [];
        unset($data['fulfilment_checklists']);

        $contract = Contract::create($data);

        foreach ($checklists as $checklist) {
            $checklist['contract_id'] = $contract->id;
            ContractFulfilmentChecklist::create($checklist);
        }

        return $contract->fresh(['partyUser', 'fulfilmentChecklists']);
    }

    public function update(int $id, array $data): Contract
    {
        $contract = Contract::findOrFail($id);
        $checklists = $data['fulfilment_checklists'] ?? null;
        unset($data['fulfilment_checklists']);

        $contract->update($data);

        if ($checklists !== null) {
            $contract->fulfilmentChecklists()->delete();
            foreach ($checklists as $checklist) {
                $checklist['contract_id'] = $contract->id;
                ContractFulfilmentChecklist::create($checklist);
            }
            $contract->updateFulfilmentStatus();
            $contract->save();
        }

        return $contract->fresh(['partyUser', 'fulfilmentChecklists']);
    }

    public function delete(int $id): bool
    {
        $contract = Contract::findOrFail($id);
        $contract->fulfilmentChecklists()->delete();
        return $contract->delete();
    }

    public function sign(int $id, array $data): Contract
    {
        $contract = Contract::findOrFail($id);
        $contract->update([
            'is_signed' => true,
            'signee' => $data['signee'] ?? null,
            'signed_on' => now(),
            'ip_address' => $data['ip_address'] ?? null,
        ]);
        return $contract->fresh(['partyUser', 'fulfilmentChecklists']);
    }
}
