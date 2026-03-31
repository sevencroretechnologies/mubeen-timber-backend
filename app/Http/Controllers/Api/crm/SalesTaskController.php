<?php

namespace App\Http\Controllers\Api\crm;

use App\Enums\TaskSource;
use App\Http\Controllers\Controller;
use App\Models\SalesTask;
use App\Models\Lead;
use App\Models\Prospect;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Exception;

class SalesTaskController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = SalesTask::with(['taskSource', 'taskType', 'assignedUser', 'details']);

            // Filter by task_source_id (Lead=1, Prospect=2, Opportunity=3)
            if ($request->has('task_source_id')) {
                $query->where('task_source_id', $request->task_source_id);
            }

            // Filter by specific source entity
            if ($request->has('source_id')) {
                $query->where('source_id', $request->source_id);
            }

            // Filter by task type
            if ($request->has('task_type_id')) {
                $query->where('task_type_id', $request->task_type_id);
            }

            // Filter by assigned user
            if ($request->has('sales_assign_id')) {
                $query->where('sales_assign_id', $request->sales_assign_id);
            }

            $perPage = $request->query('per_page', 15);
            $queryParameters = Arr::except($request->query(), ['user_id']);

            $data = $query->latest()->paginate($perPage)->appends($queryParameters);

            // Append source entity details to each task
            $data->getCollection()->each(function ($task) {
                $task->source_detail = $this->getSourceDetail($task);
            });

            return response()->json([
                'message' => 'All sales tasks retrieved successfully.',
                'data' => $data,
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'total_pages' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total_items' => $data->total(),
                    'next_page_url' => $data->nextPageUrl(),
                    'prev_page_url' => $data->previousPageUrl(),
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve sales tasks',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_source_id'  => 'required|exists:task_sources,id',
            'source_id'       => 'nullable|integer',
            'task_type_id'    => 'required|exists:task_types,id',
            'sales_assign_id' => 'nullable|exists:users,id',
            'date'            => 'required|date',
            'time'            => 'required',
            'description'     => 'nullable|string',
            'status'          => 'required|in:Open,In Progress,Closed',
        ]);

        // Validate that source_id exists in the correct table
        if (!empty($validated['source_id']) && !empty($validated['task_source_id'])) {
            $this->validateSourceId($validated['task_source_id'], $validated['source_id']);
        }

        $salesTask = \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            $task = SalesTask::create([
                'task_source_id'  => $validated['task_source_id'],
                'source_id'       => $validated['source_id'],
                'task_type_id'    => $validated['task_type_id'],
                'sales_assign_id' => $validated['sales_assign_id'],
            ]);

            \App\Models\SalesTaskDetail::create([
                'sales_task_id' => $task->id,
                'date'          => $validated['date'],
                'time'          => $validated['time'],
                'description'   => $validated['description'],
                'status'        => $validated['status'],
            ]);

            return $task;
        });

        $salesTask->load(['taskSource', 'taskType', 'assignedUser']);
        $salesTask->source_detail = $this->getSourceDetail($salesTask);

        return response()->json($salesTask, Response::HTTP_CREATED);
    }

    public function show(SalesTask $salesTask)
    {
        $salesTask->load(['taskSource', 'taskType', 'assignedUser', 'details']);
        $salesTask->source_detail = $this->getSourceDetail($salesTask);
        return response()->json($salesTask);
    }

    public function update(Request $request, SalesTask $salesTask)
    {
        $validated = $request->validate([
            'task_source_id'  => 'exists:task_sources,id',
            'source_id'       => 'nullable|integer',
            'task_type_id'    => 'exists:task_types,id',
            'sales_assign_id' => 'nullable|exists:users,id',
            'date'            => 'nullable|date',
            'time'            => 'nullable',
            'description'     => 'nullable|string',
            'status'          => 'nullable|in:Open,In Progress,Closed',
        ]);

        // Validate source_id if either field is being updated
        $taskSourceId = $validated['task_source_id'] ?? $salesTask->task_source_id;
        $sourceId = array_key_exists('source_id', $validated) ? $validated['source_id'] : $salesTask->source_id;

        if (!empty($sourceId) && !empty($taskSourceId)) {
            $this->validateSourceId($taskSourceId, $sourceId);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $salesTask) {
            // Update main task
            $salesTask->update(Arr::only($validated, [
                'task_source_id',
                'source_id',
                'task_type_id',
                'sales_assign_id'
            ]));

            // Update or create details (assuming one primary detail for now as per current structure)
            $detailData = Arr::only($validated, ['date', 'time', 'description', 'status']);
            if (!empty($detailData)) {
                $detail = $salesTask->details()->first();
                if ($detail) {
                    $detail->update($detailData);
                } else {
                    $salesTask->details()->create($detailData);
                }
            }
        });

        $salesTask->load(['taskSource', 'taskType', 'assignedUser', 'details']);
        $salesTask->source_detail = $this->getSourceDetail($salesTask);

        return response()->json($salesTask);
    }

    public function destroy(SalesTask $salesTask)
    {
        $salesTask->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Validate that source_id exists in the correct table based on task_source_id.
     */
    private function validateSourceId(int $taskSourceId, int $sourceId): void
    {
        $exists = match ($taskSourceId) {
            TaskSource::LEAD->value       => Lead::where('id', $sourceId)->exists(),
            TaskSource::PROSPECT->value   => Prospect::where('id', $sourceId)->exists(),
            TaskSource::OPPORTUNITY->value => Opportunity::where('id', $sourceId)->exists(),
            default => false,
        };

        if (!$exists) {
            $sourceName = match ($taskSourceId) {
                TaskSource::LEAD->value       => 'Lead',
                TaskSource::PROSPECT->value   => 'Prospect',
                TaskSource::OPPORTUNITY->value => 'Opportunity',
                default => 'Source',
            };

            abort(422, "The selected {$sourceName} does not exist.");
        }
    }

    /**
     * Get the source entity detail (lead/prospect/opportunity) for a sales task.
     */
    private function getSourceDetail(SalesTask $task): ?array
    {
        if (empty($task->source_id) || empty($task->task_source_id)) {
            return null;
        }

        return match ($task->task_source_id) {
            TaskSource::LEAD->value => Lead::select('id', 'first_name', 'last_name', 'company_name', 'email')
                ->find($task->source_id)?->toArray(),
            TaskSource::PROSPECT->value => Prospect::select('id', 'company_name')
                ->find($task->source_id)?->toArray(),
            TaskSource::OPPORTUNITY->value => Opportunity::select('id', 'naming_series', 'party_name', 'opportunity_amount')
                ->find($task->source_id)?->toArray(),
            default => null,
        };
    }
}
