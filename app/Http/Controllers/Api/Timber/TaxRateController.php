<?php

namespace App\Http\Controllers\Api\Timber;

use App\Enums\TaxType;
use App\Http\Controllers\Controller;
use App\Models\Timber\TaxRate;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class TaxRateController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the tax rates.
     */
    public function index()
    {
        $taxRates = TaxRate::forCurrentCompany()->get();
        return $this->success($taxRates, 'Tax rates retrieved successfully');
    }

    /**
     * Store a newly created tax rate in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'tax_type' => ['required', new Enum(TaxType::class)],
        ]);

        $taxRate = TaxRate::create($request->all());

        return $this->created($taxRate, 'Tax rate created successfully');
    }

    /**
     * Display the specified tax rate.
     */
    public function show($id)
    {
        $taxRate = TaxRate::forCurrentCompany()->findOrFail($id);
        return $this->success($taxRate, 'Tax rate retrieved successfully');
    }

    /**
     * Update the specified tax rate in storage.
     */
    public function update(Request $request, $id)
    {
        $taxRate = TaxRate::forCurrentCompany()->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'rate' => 'sometimes|required|numeric|min:0',
            'tax_type' => ['sometimes', 'required', new Enum(TaxType::class)],
        ]);

        $taxRate->update($request->all());

        return $this->success($taxRate, 'Tax rate updated successfully');
    }

    /**
     * Remove the specified tax rate from storage (Soft Delete).
     */
    public function destroy($id)
    {
        $taxRate = TaxRate::forCurrentCompany()->findOrFail($id);
        
        $taxRate->delete(); // This will perform a soft delete

        return $this->success(null, 'Tax rate deleted successfully (soft delete)');
    }
}
