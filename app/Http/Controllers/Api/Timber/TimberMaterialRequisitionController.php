<?php

namespace App\Http\Controllers\Api\Timber;

use App\Http\Controllers\Controller;
use App\Models\Timber\TimberMaterialRequisition;
use App\Services\Timber\MaterialRequisitionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimberMaterialRequisitionController extends Controller
{
    use ApiResponse;

    protected MaterialRequisitionService $requisitionService;

    public function __construct(MaterialRequisitionService $requisitionService)
    {
        $this->requisitionService = $requisitionService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = TimberMaterialRequisition::with([
                'items.woodType',
                'requestedByUser:id,name',
                'approvedByUser:id,name',
            ])->forCurrentCompany();

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('job_card_id')) {
                $query->where('job_card_id', $request->job_card_id);
            }

            if ($request->filled('project_id')) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('requisition_code', 'like', "%{$search}%");
            }

            $perPage = $request->query('per_page', 15);
            $requisitions = $query->latest()->paginate($perPage);

            return $this->paginated($requisitions, 'Material requisitions retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve requisitions: ' . $e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'job_card_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'requisition_date' => 'nullable|date',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.wood_type_id' => 'required|integer',
            'items.*.requested_quantity' => 'required|numeric|min:0.001',
            'items.*.unit' => 'required|string|max:20',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $requisition = $this->requisitionService->create($request->all());

            return $this->created($requisition, 'Material requisition created successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create requisition: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $requisition = TimberMaterialRequisition::with([
                'items.woodType',
                'requestedByUser:id,name',
                'approvedByUser:id,name',
            ])->forCurrentCompany()->findOrFail($id);

            return $this->success($requisition, 'Material requisition retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFound('Material requisition not found');
        }
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $requisition = TimberMaterialRequisition::forCurrentCompany()->findOrFail($id);
            $requisition = $this->requisitionService->approve($requisition, $request->all());

            return $this->success($requisition, 'Material requisition approved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $requisition = TimberMaterialRequisition::forCurrentCompany()->findOrFail($id);
            $requisition = $this->requisitionService->reject($requisition, $request->all());

            return $this->success($requisition, 'Material requisition rejected');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function returnMaterials(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.001',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $requisition = TimberMaterialRequisition::forCurrentCompany()->findOrFail($id);
            $requisition = $this->requisitionService->returnMaterials($requisition, $request->all());

            return $this->success($requisition, 'Materials returned successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
