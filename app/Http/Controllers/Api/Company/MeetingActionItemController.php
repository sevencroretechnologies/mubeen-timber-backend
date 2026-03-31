<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\MeetingActionItem;
use App\Services\Company\MeetingActionItemService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MeetingActionItemController extends Controller
{
    use ApiResponse;

    protected $service;

    public function __construct(MeetingActionItemService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $items = $this->service->getAll($request->all());

        return $this->success($items);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meeting_id' => 'required|exists:meetings,id',
            'title' => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:staff_members,id',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:pending,in_progress,completed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $item = $this->service->create($request->all());

        return $this->created($item, 'Action item added');
    }

    public function show(MeetingActionItem $meetingActionItem)
    {
        return $this->success($meetingActionItem->load(['meeting', 'assignedEmployee']));
    }

    public function update(Request $request, MeetingActionItem $meetingActionItem)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'assigned_to' => 'nullable|exists:staff_members,id',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:pending,in_progress,completed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $item = $this->service->update($meetingActionItem, $request->all());

        return $this->success($item, 'Action item updated');
    }

    public function destroy(MeetingActionItem $meetingActionItem)
    {
        $this->service->delete($meetingActionItem);

        return $this->noContent('Action item deleted');
    }
}
