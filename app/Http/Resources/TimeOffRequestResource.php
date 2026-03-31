<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeOffRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_date' => $this->request_date?->toDateString(),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'total_days' => (float) $this->total_days,
            'reason' => $this->reason,
            'approval_status' => $this->approval_status,
            'approval_remarks' => $this->approval_remarks,
            'approved_at' => $this->approved_at?->toIso8601String(),

            'staff_member' => $this->whenLoaded('staffMember', function () {
                return [
                    'id' => $this->staffMember->id,
                    'staff_code' => $this->staffMember->staff_code,
                    'full_name' => $this->staffMember->full_name,
                ];
            }),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'title' => $this->category->title,
                    'is_paid' => $this->category->is_paid,
                ];
            }),
            'approved_by' => $this->whenLoaded('approvedByUser', function () {
                return [
                    'id' => $this->approvedByUser->id,
                    'name' => $this->approvedByUser->name,
                ];
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
