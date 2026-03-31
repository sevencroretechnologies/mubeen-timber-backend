<?php

namespace App\Http\Controllers\Api\crm;

use App\Enums\CustomerType;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Arr;
use Exception;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Customer::with(['customerGroup:id,name', 'territory:id,territory_name', 'industry:id,name'])
                ->without([
                    'lead',
                    'opportunity',
                    'priceList',
                    'paymentTerm',
                    'primaryContact'
                ]);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            if ($request->filled('customer_type')) {
                $query->where('customer_type', $request->customer_type);
            }

            if ($request->filled('territory_id')) {
                $query->where('territory_id', $request->territory_id);
            }

            $perPage = $request->query('per_page', 10);
            $queryParameters = Arr::except($request->query(), ['user_id']);

            $data = $query->latest()->paginate($perPage)->appends($queryParameters);

            $data->getCollection()->transform(function ($item) {
                if ($item->customerGroup) {
                    $item->customer_group_name = $item->customerGroup->name;
                }
                if ($item->territory) {
                    $item->territory_name = $item->territory->territory_name;
                }
                if ($item->industry) {
                    $item->industry_name = $item->industry->name;
                }
                unset($item->customerGroup, $item->territory, $item->industry);
                return $item->makeHidden(['created_at', 'updated_at', 'deleted_at']);
            });

            return response()->json([
                'message' => 'All customers retrieved successfully.',
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
                'error' => 'Failed to retrieve customers',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'customer_type' => ['nullable', new Enum(CustomerType::class)],
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'territory_id' => 'nullable|exists:territories,id',
            'lead_id' => 'nullable|exists:leads,id',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'industry_id' => 'nullable|exists:industry_types,id',
            'default_price_list_id' => 'nullable|exists:price_lists,id',
            'payment_term_id' => 'nullable|exists:payment_terms,id',
            'customer_contact_id' => 'nullable|exists:customer_contacts,id',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'website' => 'nullable|url',
            'tax_id' => 'nullable|string',
            'billing_currency' => 'nullable|string',
            'bank_account_details' => 'nullable|string',
            'print_language' => 'nullable|string',
            'customer_details' => 'nullable|string',
        ]);

        $customer = Customer::create($validated);

        $customer->load([
            'customerGroup',
            'territory',
            'lead',
            'opportunity',
            'industry',
            'priceList',
            'paymentTerm',
            'primaryContact'
        ]);

        return response()->json($customer, 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $customer->load([
            'customerGroup',
            'territory',
            'lead',
            'opportunity',
            'industry',
            'priceList',
            'paymentTerm',
            'primaryContact'
        ]);

        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'customer_type' => ['nullable', new Enum(CustomerType::class)],
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'territory_id' => 'nullable|exists:territories,id',
            'lead_id' => 'nullable|exists:leads,id',
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'industry_id' => 'nullable|exists:industry_types,id',
            'default_price_list_id' => 'nullable|exists:price_lists,id',
            'payment_term_id' => 'nullable|exists:payment_terms,id',
            'customer_contact_id' => 'nullable|exists:customer_contacts,id',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'website' => 'nullable|url',
            'tax_id' => 'nullable|string',
            'billing_currency' => 'nullable|string',
            'bank_account_details' => 'nullable|string',
            'print_language' => 'nullable|string',
            'customer_details' => 'nullable|string',
        ]);

        $customer->update($validated);

        $customer->load([
            'customerGroup',
            'territory',
            'lead',
            'opportunity',
            'industry',
            'priceList',
            'paymentTerm',
            'primaryContact'
        ]);

        return response()->json($customer);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();
        return response()->json(null, 204);
    }
}
