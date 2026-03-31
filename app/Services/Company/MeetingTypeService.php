<?php

namespace App\Services\Company;

use App\Models\MeetingType;
use Illuminate\Database\Eloquent\Model;
use App\Services\Core\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Meeting Type Service
 *
 * Handles all business logic for meeting type management.
 */
class MeetingTypeService extends BaseService
{
    protected string $modelClass = MeetingType::class;

    protected array $defaultRelations = [];

    protected array $searchableFields = [
        'title',
    ];

    /**
     * Get all meeting types.
     */
    public function getAll(array $params = []): LengthAwarePaginator|Collection
    {
        $query = $this->query();

        if (! empty($params['search'])) {
            $query = $this->applySearch($query, $params['search']);
        }

        $query = $this->applyOrdering($query, $params);

        $paginate = ($params['paginate'] ?? 'true') !== 'false';
        $perPage = $params['per_page'] ?? $this->perPage;

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Create a new meeting type.
     */
    public function create(array $data): MeetingType
    {
        return MeetingType::create($data);
    }

    /**
     * Update a meeting type.
     */
    public function update(int|Model $type, array $data): Model
    {
        if (is_int($type)) {
            $type = $this->findOrFail($type);
        }

        $type->update($data);

        return $type->fresh();
    }

    /**
     * Delete a meeting type.
     */
    public function delete(int|Model $type): bool
    {
        if (is_int($type)) {
            $type = $this->findOrFail($type);
        }

        return $type->delete();
    }

    /**
     * Get types for dropdown.
     */
    public function getForDropdown(array $params = [], array $fields = ['id', 'title']): Collection
    {
        $query = $this->query()->select($fields);
        $query = $this->applyFilters($query, $params);

        return $query->orderBy($fields[1] ?? 'id')->get();
    }
}
