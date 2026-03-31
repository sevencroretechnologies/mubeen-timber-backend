<?php

namespace App\Http\Controllers\Api\Timber;

use App\Http\Controllers\Controller;
use App\Models\Timber\TimberSupplier;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimberSupplierController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $query = TimberSupplier::forCurrentCompany();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('supplier_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $perPage = $request->query('per_page', 15);
            $suppliers = $query->latest()->paginate($perPage);

            return $this->paginated($suppliers, 'Suppliers retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve suppliers: ' . $e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'gst_number' => 'nullable|string|max:50',
            'pan_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'payment_terms' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $data = $request->all();
            $data['supplier_code'] = TimberSupplier::generateCode();
            $data['created_by'] = auth()->id();

            $supplier = TimberSupplier::create($data);

            return $this->created($supplier, 'Supplier created successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create supplier: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $supplier = TimberSupplier::forCurrentCompany()
                ->with('purchaseOrders')
                ->findOrFail($id);

            return $this->success($supplier, 'Supplier retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFound('Supplier not found');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'gst_number' => 'nullable|string|max:50',
            'pan_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'payment_terms' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $supplier = TimberSupplier::forCurrentCompany()->findOrFail($id);
            $supplier->update($request->all());

            return $this->success($supplier, 'Supplier updated successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update supplier: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $supplier = TimberSupplier::forCurrentCompany()->findOrFail($id);
            $supplier->delete();

            return $this->noContent('Supplier deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete supplier: ' . $e->getMessage());
        }
    }
}
