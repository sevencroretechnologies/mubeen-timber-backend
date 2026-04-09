<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreEstimationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * NEW STRUCTURE:
     *   products[].product_id         → basic product reference
     *   products[].items[]            → detailed line items with calculations
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'org_id' => 'nullable|integer|exists:organizations,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'customer_id' => 'required|integer|exists:customers,id',
            'project_id' => 'required|integer|exists:projects,id',
            'description' => 'nullable|string|max:1000',
            'additional_notes' => 'nullable|string|max:2000',
            'status' => 'nullable|string|in:draft,pending,approved,rejected,cancelled,pending,collected',

            // Products Array (basic – only product_id)
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'nullable|integer|exists:products,id',

            // Items Array (nested inside each product)
            'products.*.items' => 'nullable|array',
            'products.*.items.*.name' => 'nullable|string|max:255',
            'products.*.items.*.length' => 'nullable|numeric|min:0',
            'products.*.items.*.breadth' => 'nullable|numeric|min:0',
            'products.*.items.*.height' => 'nullable|numeric|min:0',
            'products.*.items.*.thickness' => 'nullable|numeric|min:0',
            'products.*.items.*.unit_type' => 'nullable|string|in:1,2,3,4,5',
            'products.*.items.*.quantity' => 'required|integer|min:1',
            'products.*.items.*.rate' => 'nullable|numeric|min:0',
            'products.*.items.*.item_cft' => 'nullable|numeric|min:0',
            'products.*.items.*.total_amount' => 'nullable|numeric|min:0',

            // Additional Charges
            'transport_handling' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'labour_charges' => 'nullable|numeric|min:0',
            'total_cft' => 'nullable|numeric|min:0',

            // Attachments
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable',
            'deleted_attachment_ids' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer is required',
            'project_id.required' => 'Project is required',
            'products.required' => 'At least one product is required',
            'products.*.items.*.quantity.required' => 'Item quantity is required',
            'products.*.items.*.quantity.min' => 'Item quantity must be at least 1',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if (!$this->has('products')) {
            $this->merge(['products' => []]);
        }
    }
}
