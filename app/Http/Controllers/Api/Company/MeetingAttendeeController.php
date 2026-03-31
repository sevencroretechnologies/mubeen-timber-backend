<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Services\Company\MeetingAttendeeService;
use App\Models\MeetingAttendee;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MeetingAttendeeController extends Controller
{
    use ApiResponse;

    protected MeetingAttendeeService $service;

    public function __construct(MeetingAttendeeService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $attendees = $this->service->getAll($request->all());

        return $this->success($attendees);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meeting_id' => 'required|exists:meetings,id',
            'staff_member_id' => 'required|exists:staff_members,id',
            'status' => 'nullable|in:invited,accepted,declined,attended',
            'is_organizer' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $attendee = MeetingAttendee::create($request->all());

        return $this->created($attendee->load(['meeting', 'staffMember']), 'Attendee added');
    }

    public function show(MeetingAttendee $meetingAttendee)
    {
        return $this->success($meetingAttendee->load(['meeting', 'staffMember']));
    }

    public function update(Request $request, MeetingAttendee $meetingAttendee)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:invited,accepted,declined,attended',
            'is_organizer' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $meetingAttendee->update($request->all());

        return $this->success($meetingAttendee->load(['meeting', 'staffMember']), 'Attendee updated');
    }

    public function destroy(MeetingAttendee $meetingAttendee)
    {
        $meetingAttendee->delete();

        return $this->noContent('Attendee removed');
    }
}
