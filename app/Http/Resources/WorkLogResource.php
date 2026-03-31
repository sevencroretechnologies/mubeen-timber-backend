<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_date' => $this->log_date?->toDateString(),
            'status' => $this->status,
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'late_minutes' => $this->late_minutes,
            'early_leave_minutes' => $this->early_leave_minutes,
            'overtime_minutes' => $this->overtime_minutes,
            'break_minutes' => $this->break_minutes,
            'working_minutes' => $this->working_minutes,
            'working_hours_formatted' => $this->working_hours_formatted,
            'notes' => $this->notes,

            'staff_member' => $this->whenLoaded('staffMember', function () {
                return [
                    'id' => $this->staffMember->id,
                    'staff_code' => $this->staffMember->staff_code,
                    'full_name' => $this->staffMember->full_name,
                ];
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
