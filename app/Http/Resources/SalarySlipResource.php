<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalarySlipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slip_reference' => $this->slip_reference,
            'salary_period' => $this->salary_period,
            'basic_salary' => (float) $this->basic_salary,

            // Earnings breakdown
            'earnings' => [
                'benefits' => $this->benefits_breakdown,
                'incentives' => $this->incentives_breakdown,
                'bonus' => $this->bonus_breakdown,
                'overtime' => $this->overtime_breakdown,
                'contributions' => $this->contributions_breakdown,
            ],

            // Deductions breakdown
            'deductions' => [
                'recurring' => $this->deductions_breakdown,
                'advances' => $this->advances_breakdown,
                'tax' => $this->tax_breakdown,
            ],

            // Totals
            'total_earnings' => (float) $this->total_earnings,
            'total_deductions' => (float) $this->total_deductions,
            'net_payable' => (float) $this->net_payable,

            'status' => $this->status,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),

            'staff_member' => $this->whenLoaded('staffMember', function () {
                return [
                    'id' => $this->staffMember->id,
                    'staff_code' => $this->staffMember->staff_code,
                    'full_name' => $this->staffMember->full_name,
                    'division' => $this->staffMember->division?->title,
                    'job_title' => $this->staffMember->jobTitle?->title,
                ];
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
