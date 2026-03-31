<?php

namespace App\Services\Company;

use App\Models\MeetingActionItem;
use App\Services\Core\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Meeting Action Item Service
 */
class MeetingActionItemService extends BaseService
{
    protected string $modelClass = MeetingActionItem::class;

    protected array $defaultRelations = ['meeting', 'assignedEmployee'];

    protected array $searchableFields = [
        'title',
    ];

    /**
     * Get all action items with enhanced search.
     */
    public function getAll(array $params = []): LengthAwarePaginator|Collection
    {
        $query = $this->query()->with($this->defaultRelations);

        if (! empty($params['meeting_id'])) {
            $query->where('meeting_id', $params['meeting_id']);
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                // Search in own fields
                foreach ($this->searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
                // Search in related meeting title
                $q->orWhereHas('meeting', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
                // Search in related assigned employee
                $q->orWhereHas('assignedEmployee', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%");
                });
            });
        }

        $query = $this->applyOrdering($query, $params);

        $paginate = ($params['paginate'] ?? 'true') !== 'false';
        $perPage = $params['per_page'] ?? $this->perPage;

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function create(array $data): MeetingActionItem
    {
        $item = MeetingActionItem::create($data);
        return $item->fresh($this->defaultRelations);
    }

    public function update(int|Model $item, array $data): Model
    {
        if (is_int($item)) {
            $item = $this->findOrFail($item);
        }

        $item->update($data);

        return $item->fresh($this->defaultRelations);
    }

    public function delete(int|Model $item): bool
    {
        if (is_int($item)) {
            $item = $this->findOrFail($item);
        }

        return $item->delete();
    }
}
