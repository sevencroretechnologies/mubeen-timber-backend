<?php

namespace App\Services\Company;

use App\Models\MeetingMinutes;
use App\Services\Core\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Meeting Minute Service
 */
class MeetingMinuteService extends BaseService
{
    protected string $modelClass = MeetingMinutes::class;

    protected array $defaultRelations = ['meeting', 'creator'];

    protected array $searchableFields = [
        'content',
    ];

    /**
     * Get all minutes with enhanced search.
     */
    public function getAll(array $params = []): LengthAwarePaginator|Collection
    {
        $query = $this->query()->with($this->defaultRelations);

        if (! empty($params['meeting_id'])) {
            $query->where('meeting_id', $params['meeting_id']);
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
            });
        }

        $query = $this->applyOrdering($query, $params);

        $paginate = ($params['paginate'] ?? 'true') !== 'false';
        $perPage = $params['per_page'] ?? $this->perPage;

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function create(array $data): MeetingMinutes
    {
        $data['created_by'] = auth()->id();
        return MeetingMinutes::create($data);
    }

    public function update(int|Model $minute, array $data): Model
    {
        if (is_int($minute)) {
            $minute = $this->findOrFail($minute);
        }

        $minute->update($data);

        return $minute->fresh($this->defaultRelations);
    }

    public function delete(int|Model $minute): bool
    {
        if (is_int($minute)) {
            $minute = $this->findOrFail($minute);
        }

        return $minute->delete();
    }
}
