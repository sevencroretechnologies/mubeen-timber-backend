<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    protected $orgService;

    public function __construct(OrganizationService $orgService)
    {
        $this->orgService = $orgService;
    }

    /**
     * List all Companies with pagination
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', null);

            // org_id is automatically injected by InjectOrgAndCompany middleware
            $orgId = $request->input('org_id');

            $companies = $this->orgService->getPaginatedCompanies($perPage, $search, $orgId);

            return response()->json([
                'success' => true,
                'data' => $companies->items(),
                'meta' => [
                    'current_page' => $companies->currentPage(),
                    'total' => $companies->total(),
                    'per_page' => $companies->perPage(),
                    'last_page' => $companies->lastPage(),
                    'from' => $companies->firstItem(),
                    'to' => $companies->lastItem(),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created Company.
     */
    public function store(Request $request)
    {
        // org_id is automatically injected by InjectOrgAndCompany middleware
        $orgId = $request->input('org_id');

        if (!$orgId) {
            return response()->json([
                'success' => false,
                'message' => 'You must belong to an organization to create a company',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            // Company fields
            'company_name'     => 'required|string|max:255',
            'address'          => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'company_phone'    => 'nullable|string|max:30',
            'company_email'    => 'nullable|email|max:255',
            'website'          => 'nullable|url|max:255',
            'company_logo'     => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

            // User fields
            'user_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'nullable|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Handle company logo upload
            $logoPath = null;
            if ($request->hasFile('company_logo')) {
                $logoPath = $request->file('company_logo')->store('company_logos', 'public');
            }

            // 1️⃣ Create Company with org_id (injected by middleware)
            $company = \App\Models\Company::create([
                'org_id'           => $orgId,
                'company_name'     => $request->company_name,
                'address'          => $request->address,
                'shipping_address' => $request->shipping_address,
                'company_phone'    => $request->company_phone,
                'email'            => $request->company_email,
                'website'          => $request->website,
                'company_logo'     => $logoPath,
            ]);

            // 2️⃣ Create User and STORE company_id
            $companyAdmin = \App\Models\User::create([
                'name' => $request->user_name,
                'email' => $request->email,
                'password' => Hash::make($request->password ?? 'password123'),
                'org_id' => $orgId, // Inherit org from authenticated user
                'company_id' => $company->id, // ✅ STORED HERE
                'is_active' => true,
            ]);

            $companyAdmin->assignRole('company');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company and user created successfully',
                'data' => [
                    'company' => $company,
                    'user' => $companyAdmin,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create company',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Company Details
     */
    public function show($id)
    {
        try {
            $company = $this->orgService->getCompany($id);
            if (!$company) {
                return response()->json(['success' => false, 'message' => 'Company not found'], 404);
            }
            return response()->json(['success' => true, 'data' => $company]);
        } catch (Exception $e) {
             return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update Company
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'org_id'           => 'sometimes|exists:organizations,id',
            'company_name'     => 'sometimes|string|max:255',
            'address'          => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'company_phone'    => 'nullable|string|max:30',
            'company_email'    => 'nullable|email|max:255',
            'website'          => 'nullable|url|max:255',
            'company_logo'     => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $company = \App\Models\Company::findOrFail($id);

            $data = $request->only([
                'org_id', 'company_name', 'address',
                'shipping_address', 'company_phone', 'website',
            ]);

            // Map company_email → email
            if ($request->filled('company_email')) {
                $data['email'] = $request->company_email;
            }

            // Handle logo upload / removal
            if ($request->hasFile('company_logo')) {
                // Replace with new logo
                if ($company->company_logo) {
                    Storage::disk('public')->delete($company->company_logo);
                }
                $data['company_logo'] = $request->file('company_logo')->store('company_logos', 'public');
            } elseif ($request->input('remove_logo') == '1') {
                // Remove logo without replacing
                if ($company->company_logo) {
                    Storage::disk('public')->delete($company->company_logo);
                }
                $data['company_logo'] = null;
            }

            $company->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Company updated successfully',
                'data'    => $company->fresh(),
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete Company
     */
    public function destroy($id)
    {
        try {
            $this->orgService->deleteCompany($id);
            return response()->json([
                'success' => true,
                'message' => 'Company deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
