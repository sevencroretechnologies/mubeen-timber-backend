<?php

namespace App\Services;

use App\Models\Estimation;
use App\Models\EstimationProduct;
use App\Models\EstimationOtherCharge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstimationService
{
    /**
     * Store complete estimation with products and charges in a single transaction.
     *
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function storeCompleteEstimation(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Step 1: Create Estimation
            $estimation = $this->createEstimation($data);

            // Step 2: Create Products
            $products = $this->createProducts($estimation->id, $data);

            // Step 3: Calculate total CFT from products (if not provided)
            $totalCft = $this->calculateTotalCft($products);

            // Step 4: Create Other Charges
            $otherCharges = $this->createOtherCharges($estimation->id, $data, $totalCft);

            // Step 5: Calculate and save grand total
            $grandTotal = $this->calculateGrandTotal($products, $otherCharges);
            $estimation->update(['grand_total' => $grandTotal]);

            // Step 6: Load relationships
            $estimation->load(['project', 'customer', 'products.product', 'otherCharge']);

            Log::info('Estimation created successfully', [
                'estimation_id' => $estimation->id,
                'customer_id' => $data['customer_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
            ]);

            return [
                'estimation' => $estimation,
                'products' => $products,
                'other_charges' => $otherCharges,
                'total_cft' => $totalCft,
                'grand_total' => $grandTotal,
            ];
        });
    }

    /**
     * Update an existing estimation with products and charges.
     *
     * @param int $estimationId
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function updateCompleteEstimation(int $estimationId, array $data): array
    {
        return DB::transaction(function () use ($estimationId, $data) {
            $estimation = Estimation::with(['products', 'otherCharge'])->findOrFail($estimationId);

            // Update basic info
            $estimation->update([
                'description' => $data['description'] ?? $estimation->description,
                'additional_notes' => $data['additional_notes'] ?? $estimation->additional_notes,
                'status' => $data['status'] ?? $estimation->status,
            ]);

            // Delete existing products and recreate
            if (isset($data['products'])) {
                $estimation->products()->delete();
                $products = $this->createProducts($estimation->id, $data);
            } else {
                $products = $estimation->products;
            }

            // Update or create other charges
            if (isset($data['transport_handling']) ||
                isset($data['discount']) ||
                isset($data['tax']) ||
                isset($data['labour_charges']) ||
                isset($data['total_cft'])) {

                $totalCft = $this->calculateTotalCft($products ?? $estimation->products);
                $otherCharges = $this->updateOrCreateOtherCharges($estimation->id, $data, $totalCft);
            } else {
                $otherCharges = $estimation->otherCharge;
            }

            // Calculate and save grand total
            $grandTotal = $this->calculateGrandTotal($products ?? $estimation->products, $otherCharges);
            $estimation->update(['grand_total' => $grandTotal]);

            $estimation->load(['project', 'customer', 'products.product', 'otherCharge']);

            return [
                'estimation' => $estimation,
                'products' => $products ?? $estimation->products,
                'other_charges' => $otherCharges,
                'grand_total' => $grandTotal,
            ];
        });
    }

    /**
     * Create the base estimation record.
     */
    private function createEstimation(array $data): Estimation
    {
        return Estimation::create([
            'org_id' => $data['org_id'] ?? null,
            'company_id' => $data['company_id'] ?? null,
            'customer_id' => $data['customer_id'],
            'project_id' => $data['project_id'],
            'description' => $data['description'] ?? null,
            'additional_notes' => $data['additional_notes'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ]);
    }

    /**
     * Create products for the estimation.
     *
     * @return \Illuminate\Support\Collection
     */
    private function createProducts(int $estimationId, array $data): \Illuminate\Support\Collection
    {
        $productsCollection = collect();

        foreach ($data['products'] as $productData) {
            $product = EstimationProduct::create([
                'estimation_id' => $estimationId,
                'org_id' => $data['org_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'product_id' => $productData['product_id'] ?? null,
                'customer_id' => $data['customer_id'],
                'project_id' => $data['project_id'],
                'length' => $productData['length'] ?? 0,
                'breadth' => $productData['breadth'] ?? 0,
                'height' => $productData['height'] ?? 0,
                'thickness' => $productData['thickness'] ?? 0,
                'cft_calculation_type' => $productData['cft_calculation_type'] ?? '1',
                'quantity' => $productData['quantity'] ?? 1,
                'cft' => $productData['cft'] ?? 0,
                'cost_per_cft' => $productData['rate'] ?? $productData['cost_per_cft'] ?? 0,
                'total_amount' => $productData['total_amount'] ?? 0,
            ]);

            // Calculate CFT if not provided and type is not manual
            if (empty($productData['cft']) && $productData['cft_calculation_type'] !== '5') {
                $product->cft = round($product->calculateCft(), 2);
                $product->total_amount = round($product->calculateTotalAmount(), 2);
                $product->save();
            }

            $productsCollection->push($product);
        }

        return $productsCollection;
    }

    /**
     * Create other charges record.
     */
    private function createOtherCharges(int $estimationId, array $data, float $totalCft): ?EstimationOtherCharge
    {
        $hasCharges = isset($data['transport_handling']) ||
                       isset($data['discount']) ||
                       isset($data['tax']) ||
                       isset($data['labour_charges']) ||
                       isset($data['total_cft']);

        if (!$hasCharges) {
            return null;
        }

        return EstimationOtherCharge::create([
            'estimation_id' => $estimationId,
            'org_id' => $data['org_id'] ?? null,
            'company_id' => $data['company_id'] ?? null,
            'labour_charges' => $data['labour_charges'] ?? 0,
            'transport_and_handling' => $data['transport_handling'] ?? $data['transport_cost'] ?? 0,
            'discount' => $data['discount'] ?? 0,
            'approximate_tax' => $data['tax'] ?? 0,
            'overall_total_cft' => $data['total_cft'] ?? $totalCft,
            'other_description_amount' => $data['other_description_amount'] ?? 0,
            'other_description' => $data['other_description'] ?? null,
        ]);
    }

    /**
     * Update or create other charges.
     */
    private function updateOrCreateOtherCharges(int $estimationId, array $data, float $totalCft): EstimationOtherCharge
    {
        return EstimationOtherCharge::updateOrCreate(
            ['estimation_id' => $estimationId],
            [
                'org_id' => $data['org_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'labour_charges' => $data['labour_charges'] ?? 0,
                'transport_and_handling' => $data['transport_handling'] ?? $data['transport_cost'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'approximate_tax' => $data['tax'] ?? 0,
                'overall_total_cft' => $data['total_cft'] ?? $totalCft,
                'other_description_amount' => $data['other_description_amount'] ?? 0,
                'other_description' => $data['other_description'] ?? null,
            ]
        );
    }

    /**
     * Calculate total CFT from all products.
     */
    private function calculateTotalCft($products): float
    {
        return (float) $products->sum(function ($product) {
            return ($product->cft ?? 0) * ($product->quantity ?? 1);
        });
    }

    /**
     * Calculate grand total from products and other charges.
     */
    private function calculateGrandTotal($products, $otherCharges): float
    {
        // Sum of all product totals
        $productsTotal = $products->sum(function ($product) {
            return $product->total_amount ?? 0;
        });

        // Add charges
        $chargesTotal = 0;
        if ($otherCharges) {
            $chargesTotal += ($otherCharges->labour_charges ?? 0);
            $chargesTotal += ($otherCharges->transport_and_handling ?? 0);
            $chargesTotal += ($otherCharges->approximate_tax ?? 0);
            $chargesTotal -= ($otherCharges->discount ?? 0);
        }

        return round($productsTotal + $chargesTotal, 2);
    }

    /**
     * Delete an estimation with all related data.
     */
    public function deleteEstimation(int $estimationId): void
    {
        DB::transaction(function () use ($estimationId) {
            $estimation = Estimation::findOrFail($estimationId);

            // Products will be cascade deleted due to foreign key
            // Other charges will be cascade deleted due to foreign key
            $estimation->delete();
        });
    }
}
