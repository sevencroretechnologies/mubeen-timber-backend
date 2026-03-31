<?php

namespace App\Services\Recruitment;

use App\Models\JobCategory;
use App\Services\Core\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Job Category Service
 *
 * Handles all business logic for job category management.
 */
class JobCategoryService extends BaseService
{
    protected string $modelClass = JobCategory::class;

    protected array $defaultRelations = [];

    protected array $searchableFields = [
        'title',
    ];

    /**
     * Get all job categories.
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
     * Get categories for dropdown.
     */
    public function getForDropdown(array $params = [], array $fields = ['id', 'title']): Collection
    {
        return $this->query()
            ->select($fields)
            ->orderBy($fields[1] ?? 'title')
            ->get();
    }
}
