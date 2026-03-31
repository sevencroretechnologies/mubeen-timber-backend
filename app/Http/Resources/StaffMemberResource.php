<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'staff_code' => $this->staff_code,
            'full_name' => $this->full_name,
            'personal_email' => $this->personal_email,
            'mobile_number' => $this->mobile_number,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
            'home_address' => $this->home_address,
            'nationality' => $this->nationality,
            'passport_number' => $this->passport_number,
            'country_code' => $this->country_code,
            'region' => $this->region,
            'city_name' => $this->city_name,
            'postal_code' => $this->postal_code,

            // Employment
            'biometric_id' => $this->biometric_id,
            'office_location_id' => $this->office_location_id,
            'division_id' => $this->division_id,
            'job_title_id' => $this->job_title_id,
            'hire_date' => $this->hire_date?->toDateString(),
            'employment_status' => $this->employment_status,
            'compensation_type' => $this->compensation_type,
            'base_salary' => (float) $this->base_salary,

            // Relationships
            'office_location' => $this->whenLoaded('officeLocation', function () {
                return [
                    'id' => $this->officeLocation->id,
                    'title' => $this->officeLocation->title,
                ];
            }),
            'division' => $this->whenLoaded('division', function () {
                return [
                    'id' => $this->division->id,
                    'title' => $this->division->title,
                ];
            }),
            'job_title' => $this->whenLoaded('jobTitle', function () {
                return [
                    'id' => $this->jobTitle->id,
                    'title' => $this->jobTitle->title,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                    'is_active' => $this->user->is_active,
                ];
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
