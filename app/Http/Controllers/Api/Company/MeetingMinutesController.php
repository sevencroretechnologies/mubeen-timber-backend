<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Services\Company\MeetingMinuteService;
use App\Models\MeetingMinutes;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MeetingMinutesController extends Controller
{
    use ApiResponse;

    protected MeetingMinuteService $service;

    public function __construct(MeetingMinuteService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $minutes = $this->service->getAll($request->all());

        return $this->success($minutes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meeting_id' => 'required|exists:meetings,id',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $minutes = MeetingMinutes::create([
            'meeting_id' => $request->meeting_id,
            'content' => $request->content,
            'created_by' => auth()->id(),
        ]);

        return $this->created($minutes->load(['meeting', 'creator']), 'Minutes added');
    }

    public function show(MeetingMinutes $meetingMinute)
    {
        return $this->success($meetingMinute->load(['meeting', 'creator']));
    }

    public function update(Request $request, MeetingMinutes $meetingMinute)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $meetingMinute->update($request->all());

        return $this->success($meetingMinute->load(['meeting', 'creator']), 'Minutes updated');
    }

    public function destroy(MeetingMinutes $meetingMinute)
    {
        $meetingMinute->delete();

        return $this->noContent('Minutes deleted');
    }
}
