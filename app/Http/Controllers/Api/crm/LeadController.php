<?php

namespace App\Http\Controllers\Api\crm;

use App\Enums\Gender;
use App\Enums\QualificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Prospect;
use App\Models\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Exception;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Lead::with(['status', 'source', 'requestType', 'industry']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('mobile_no', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status_id')) {
                $query->where('status_id', $request->status_id);
            }

            if ($request->filled('source_id')) {
                $query->where('source_id', $request->source_id);
            }

            if ($request->filled('industry_id')) {
                $query->where('industry_id', $request->industry_id);
            }

            $perPage = $request->query('per_page', 15);
            $queryParameters = Arr::except($request->query(), ['user_id']);

            $data = $query->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->appends($queryParameters);

            $data->getCollection()->transform(function ($item) {
                if ($item->status) {
                    $item->status_name = $item->status->status_name;
                }
                if ($item->source) {
                    $item->source_name = $item->source->name;
                }
                unset($item->status, $item->source);
                return $item->makeHidden(['created_at', 'updated_at', 'deleted_at']);
            });

            return response()->json([
                'message' => 'All leads retrieved successfully.',
                'data' => $data->items(),
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
                'error' => 'Failed to retrieve leads',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getLead(): JsonResponse
    {
        $lead = Lead::get();

        return response()->json([
            'message' => 'All Leads retrieved successfully.',
            'data' => $lead,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'series' => 'nullable|string|max:255',
            'salutation' => 'nullable|string|max:50',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'gender' => 'nullable|string|in:' . implode(',', Gender::values()),
            'status_id' => 'nullable|integer|exists:statuses,id',
            'source_id' => 'nullable|integer|exists:sources,id',
            'request_type_id' => 'nullable|integer|exists:request_types,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile_no' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'whatsapp_no' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'annual_revenue' => 'nullable|numeric|min:0',
            'no_of_employees' => 'nullable|string|max:50',
            'industry_id' => 'nullable|integer|exists:industry_types,id',
            'qualification_status' => 'nullable|string|in:' . implode(',', QualificationStatus::values()),
            'qualified_by' => 'nullable|integer|exists:users,id',
            'qualified_on' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($validated) {
            $lead = Lead::create($validated);
            $this->syncProspect($lead);
            return response()->json($lead->fresh(['status', 'source', 'requestType', 'industry']), 201);
        });
    }

    public function show(int $id): JsonResponse
    {
        $lead = Lead::with(['status', 'source', 'requestType', 'industry'])->findOrFail($id);
        return response()->json($lead);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'series' => 'nullable|string|max:255',
            'salutation' => 'nullable|string|max:50',
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'gender' => 'nullable|string|in:' . implode(',', Gender::values()),
            'status_id' => 'nullable|integer|exists:statuses,id',
            'source_id' => 'nullable|integer|exists:sources,id',
            'request_type_id' => 'nullable|integer|exists:request_types,id',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'mobile_no' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'whatsapp_no' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'annual_revenue' => 'nullable|numeric|min:0',
            'no_of_employees' => 'nullable|string|max:50',
            'industry_id' => 'nullable|integer|exists:industry_types,id',
            'qualification_status' => 'nullable|string|in:' . implode(',', QualificationStatus::values()),
            'qualified_by' => 'nullable|integer|exists:users,id',
            'qualified_on' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $lead = Lead::findOrFail($id);
            $lead->update($validated);
            $this->syncProspect($lead);
            return response()->json($lead->fresh(['status', 'source', 'requestType', 'industry']));
        });
    }

    private function syncProspect(Lead $lead): void
    {
        $lead->load('status', 'source', 'industry');

        if ($lead->status && $lead->status->status_name === 'Interest') {
            // Find existing prospect linked to this lead
            $prospect = $lead->prospects()->first();

            if (!$prospect && $lead->company_name) {
                // If not linked, try finding by company name
                $prospect = Prospect::where('company_name', $lead->company_name)->first();
            }

            $prospectData = [
                'company_name' => $lead->company_name,
                'industry' => $lead->industry?->name,
                'annual_revenue' => $lead->annual_revenue,
                'no_of_employees' => $lead->no_of_employees,
                'email' => $lead->email,
                'phone' => $lead->mobile_no ?? $lead->phone,
                'city' => $lead->city,
                'state' => $lead->state,
                'country' => $lead->country,
                'website' => $lead->website,
                'source' => $lead->source?->name,
                'status' => $lead->status->status_name,
            ];

            if ($prospect) {
                $prospect->update($prospectData);
            } else {
                $prospect = Prospect::create($prospectData);
            }

            // Link lead to prospect
            $prospect->leads()->syncWithoutDetaching([
                $lead->id => [
                    'lead_name' => trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? '')),
                    'email' => $lead->email,
                    'mobile_no' => $lead->mobile_no,
                    'status' => $lead->status->status_name,
                ]
            ]);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        $lead->delete();
        return response()->json(null, 204);
    }
}
