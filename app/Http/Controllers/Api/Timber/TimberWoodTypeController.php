<?php

namespace App\Http\Controllers\Api\Timber;

use App\Http\Controllers\Controller;
use App\Models\Timber\TimberWoodType;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimberWoodTypeController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $query = TimberWoodType::forCurrentCompany();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $perPage = $request->query('per_page', 15);
            $woodTypes = $query->latest()->paginate($perPage);

            return $this->paginated($woodTypes, 'Wood types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve wood types: ' . $e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'default_rate' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $data = $request->all();

            if (empty($data['code'])) {
                $data['code'] = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $data['name']), 0, 6))
                    . '-' . str_pad(TimberWoodType::forCurrentCompany()->count() + 1, 3, '0', STR_PAD_LEFT);
            }

            $woodType = TimberWoodType::create($data);

            return $this->created($woodType, 'Wood type created successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create wood type: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $woodType = TimberWoodType::forCurrentCompany()
                ->with('stockLedger')
                ->findOrFail($id);

            return $this->success($woodType, 'Wood type retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFound('Wood type not found');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'default_rate' => 'sometimes|required|numeric|min:0',
            'unit' => 'sometimes|required|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $woodType = TimberWoodType::forCurrentCompany()->findOrFail($id);
            $woodType->update($request->all());

            return $this->success($woodType, 'Wood type updated successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update wood type: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $woodType = TimberWoodType::forCurrentCompany()->findOrFail($id);

            if ($woodType->stockLedger()->exists()) {
                return $this->error('Cannot delete wood type with existing stock records', 422);
            }

            $woodType->delete();

            return $this->noContent('Wood type deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete wood type: ' . $e->getMessage());
        }
    }
}
