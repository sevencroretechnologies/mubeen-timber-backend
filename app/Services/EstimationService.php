<?php

namespace App\Services;

use App\Models\Estimation;
use App\Models\EstimationProduct;
use App\Models\EstimationOtherCharge;
use App\Models\EstimationAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

            // Step 6: Create Attachments if provided
            $attachments = null;
            if (isset($data['attachments']) && is_array($data['attachments'])) {
                $attachments = $this->createAttachments($estimation->id, $data);
            }

            // Step 7: Load relationships
            $estimation->load(['project', 'customer', 'products.product', 'otherCharge', 'attachments']);

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
                'attachments' => $attachments,
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
            $estimation = Estimation::with(['products', 'otherCharge', 'attachments'])->findOrFail($estimationId);

            // Update basic info
            $estimation->update([
                'description' => $data['description'] ?? $estimation->description,
                'additional_notes' => $data['additional_notes'] ?? $estimation->additional_notes,
                'status' => $data['status'] ?? $estimation->status,
            ]);

            // Process deleted products
            if (!empty($data['deleted_product_ids']) && is_array($data['deleted_product_ids'])) {
                EstimationProduct::whereIn('id', $data['deleted_product_ids'])
                    ->where('estimation_id', $estimation->id) // Security check
                    ->delete();
            }

            // Update or create products
            if (isset($data['products'])) {
                $products = $this->upsertProducts($estimation->id, $data, $estimation);
            } else {
                $products = $estimation->products;
            }

            // Update or create other charges
            if (
                isset($data['labour_charges']) ||
                isset($data['transport_handling']) ||
                isset($data['discount']) ||
                isset($data['tax']) ||
                isset($data['total_cft'])
            ) {

                $totalCft = $this->calculateTotalCft($products ?? $estimation->products);
                $otherCharges = $this->updateOrCreateOtherCharges($estimation->id, $data, $totalCft);
            } else {
                $otherCharges = $estimation->otherCharge;
            }

            // Update attachments if provided
            $attachments = $estimation->attachments;
            if (!empty($data['remove_attachment'])) {
                $estimation->attachments()->delete();
                $attachments = collect([]);
            } elseif (isset($data['attachments']) && is_array($data['attachments'])) {
                // Delete existing attachments
                $estimation->attachments()->delete();
                // Create new attachments
                $attachments = $this->createAttachments($estimation->id, $data);
            }

            // Calculate and save grand total
            $grandTotal = $this->calculateGrandTotal($products ?? $estimation->products, $otherCharges);
            $estimation->update(['grand_total' => $grandTotal]);

            $estimation->load(['project', 'customer', 'products.product', 'otherCharge', 'attachments']);

            return [
                'estimation' => $estimation,
                'products' => $products ?? $estimation->products,
                'other_charges' => $otherCharges,
                'grand_total' => $grandTotal,
                'attachments' => $attachments,
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
     * Upsert products for the estimation.
     *
     * @return \Illuminate\Support\Collection
     */
    private function upsertProducts(int $estimationId, array $data, ?Estimation $estimation = null): \Illuminate\Support\Collection
    {
        $productsCollection = collect();

        foreach ($data['products'] as $productData) {
            $productAttributes = [
                'org_id' => $data['org_id'] ?? $estimation?->org_id ?? null,
                'company_id' => $data['company_id'] ?? $estimation?->company_id ?? null,
                'product_id' => $productData['product_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? $estimation?->customer_id,
                'project_id' => $data['project_id'] ?? $estimation?->project_id,
                'length' => $productData['length'] ?? 0,
                'breadth' => $productData['breadth'] ?? 0,
                'height' => $productData['height'] ?? 0,
                'thickness' => $productData['thickness'] ?? 0,
                'cft_calculation_type' => $productData['cft_calculation_type'] ?? '1',
                'quantity' => $productData['quantity'] ?? 1,
                'cft' => $productData['cft'] ?? 0,
                'cost_per_cft' => $productData['rate'] ?? $productData['cost_per_cft'] ?? 0,
                'total_amount' => $productData['total_amount'] ?? 0,
            ];

            if (!empty($productData['id'])) {
                $product = EstimationProduct::where('id', $productData['id'])
                    ->where('estimation_id', $estimationId)
                    ->firstOrFail();
                $product->update($productAttributes);
            } else {
                $productAttributes['estimation_id'] = $estimationId;
                $product = EstimationProduct::create($productAttributes);
            }

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
     * Create products for the estimation.
     *
     * @return \Illuminate\Support\Collection
     */
    private function createProducts(int $estimationId, array $data, ?Estimation $estimation = null): \Illuminate\Support\Collection
    {
        return $this->upsertProducts($estimationId, $data, $estimation);
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
     * Create attachments for the estimation.
     * Expects attachments data with file_path or base64 data.
     */
    private function createAttachments(int $estimationId, array $data): \Illuminate\Support\Collection
    {
        $attachmentsCollection = collect();

        if (!isset($data['attachments']) || !is_array($data['attachments'])) {
            return $attachmentsCollection;
        }

        foreach ($data['attachments'] as $attachmentData) {
            if (empty($attachmentData)) {
                continue;
            }

            // If attachment is a string (file path or base64), handle it
            $imagePath = null;
            $description = null;

            if (is_string($attachmentData)) {
                // Check if it's base64 data
                if (preg_match('/^data:image\/(\w+);base64,/i', $attachmentData, $matches)) {
                    $imagePath = $this->saveBase64Image($attachmentData, $estimationId);
                } else {
                    // Assume it's already a file path
                    $imagePath = $attachmentData;
                }
            } elseif (is_array($attachmentData)) {
                $imagePath = $attachmentData['image'] ?? null;
                $description = $attachmentData['description'] ?? null;

                // Handle base64 if present
                if (isset($attachmentData['base64']) && preg_match('/^data:image\/(\w+);base64,/i', $attachmentData['base64'])) {
                    $imagePath = $this->saveBase64Image($attachmentData['base64'], $estimationId);
                }
            }

            if ($imagePath) {
                $attachment = EstimationAttachment::create([
                    'estimation_id' => $estimationId,
                    'org_id' => $data['org_id'] ?? null,
                    'company_id' => $data['company_id'] ?? null,
                    'image' => $imagePath,
                    'description' => $description,
                ]);
                $attachmentsCollection->push($attachment);
            }
        }

        return $attachmentsCollection;
    }

    /**
     * Save base64 image to file system.
     */
    private function saveBase64Image(string $base64Data, int $estimationId): string
    {
        // Extract the mime type and base64 content
        preg_match('/^data:image\/(\w+);base64,/i', $base64Data, $matches);
        $extension = $matches[1] ?? 'png';

        // Remove the data URI scheme
        $base64Content = preg_replace('/^data:image\/\w+;base64,/i', '', $base64Data);
        $imageData = base64_decode($base64Content);

        if ($imageData === false) {
            throw new \Exception('Invalid base64 image data');
        }

        // Create directory if it doesn't exist
        $directory = public_path("uploads/estimations/{$estimationId}");
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Generate unique filename
        $filename = time() . '_' . uniqid() . ".{$extension}";
        $filepath = "uploads/estimations/{$estimationId}/{$filename}";

        // Save the file
        file_put_contents(public_path($filepath), $imageData);

        return $filepath;
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
