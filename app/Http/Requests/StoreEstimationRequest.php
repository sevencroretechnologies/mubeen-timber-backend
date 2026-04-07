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
            'status' => 'nullable|string|in:draft,pending,approved,rejected,cancelled,partially_collected,collected',

            // Products Array
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'nullable|integer|exists:products,id',
            'products.*.length' => 'nullable|numeric|min:0',
            'products.*.breadth' => 'nullable|numeric|min:0',
            'products.*.height' => 'nullable|numeric|min:0',
            'products.*.thickness' => 'nullable|numeric|min:0',
            'products.*.cft_calculation_type' => 'required|string|in:1,2,3,4,5',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.cft' => 'nullable|numeric|min:0',
            'products.*.rate' => 'nullable|numeric|min:0',
            'products.*.cost_per_cft' => 'nullable|numeric|min:0',
            'products.*.total_amount' => 'nullable|numeric|min:0',

            // Additional Charges
            'transport_handling' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'labour_charges' => 'nullable|numeric|min:0',
            'total_cft' => 'nullable|numeric|min:0',
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
            'products.*.quantity.required' => 'Product quantity is required',
            'products.*.quantity.min' => 'Product quantity must be at least 1',
            'products.*.cft_calculation_type.required' => 'Calculation type is required for each product',
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
        // Ensure products array exists
        if (!$this->has('products')) {
            $this->merge(['products' => []]);
        }
    }
}
