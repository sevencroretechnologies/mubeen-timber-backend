<?php

namespace App\Services\Company;

use App\Models\MeetingAttendee;
use App\Services\Core\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Meeting Attendee Service
 */
class MeetingAttendeeService extends BaseService
{
    protected string $modelClass = MeetingAttendee::class;

    protected array $defaultRelations = ['meeting', 'staffMember'];

    protected array $searchableFields = []; // No direct searchable fields, all are relations

    /**
     * Get all attendees with enhanced search.
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
                // Search in related staff member
                $q->whereHas('staffMember', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%");
                });
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

    public function create(array $data): MeetingAttendee
    {
        return MeetingAttendee::create($data);
    }

    public function update(int|Model $attendee, array $data): Model
    {
        if (is_int($attendee)) {
            $attendee = $this->findOrFail($attendee);
        }

        $attendee->update($data);

        return $attendee->fresh($this->defaultRelations);
    }

    public function delete(int|Model $attendee): bool
    {
        if (is_int($attendee)) {
            $attendee = $this->findOrFail($attendee);
        }

        return $attendee->delete();
    }
}
