<?php

namespace App\Services;

use App\Models\Estimation;
use App\Models\EstimationProduct;
use App\Models\EstimationProductsItem;
use App\Models\EstimationOtherCharge;
use App\Models\EstimationAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EstimationService
{
    /**
     * Store complete estimation with products, items, and charges in a single transaction.
     *
     * NEW FLOW:
     *   1. Create Estimation
     *   2. Create Products (basic – only product_id)
     *   3. Create Items for each product (dimensions, CFT, rate, total)
     *   4. Aggregate item totals → product total
     *   5. Create Other Charges
     *   6. Calculate grand total → save to estimation
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

            // Step 2: Create Products (basic) + Step 3: Create Items
            $products = $this->createProductsWithItems($estimation, $data);

            // Step 4: Calculate total CFT from all items
            $totalCft = $this->calculateTotalCft($estimation);

            // Step 5: Create Other Charges
            $otherCharges = $this->createOtherCharges($estimation->id, $data, $totalCft);

            // Step 6: Calculate and save grand total
            $grandTotal = $this->calculateGrandTotal($estimation, $otherCharges);
            $estimation->update(['grand_total' => $grandTotal]);

            // Load relationships
            $estimation->load([
                'project',
                'customer',
                'products.product',
                'products.items',
                'otherCharge',
                'attachments',
            ]);

            Log::info('Estimation created successfully', [
                'estimation_id' => $estimation->id,
                'customer_id' => $data['customer_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'total_products' => $products->count(),
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
     * Update an existing estimation with products, items, and charges.
     *
     * @param int $estimationId
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function updateCompleteEstimation(int $estimationId, array $data): array
    {
        return DB::transaction(function () use ($estimationId, $data) {
            $estimation = Estimation::with(['products.items', 'otherCharge', 'attachments'])->findOrFail($estimationId);

            // Update basic info
            $estimation->update([
                'description' => $data['description'] ?? $estimation->description,
                'additional_notes' => $data['additional_notes'] ?? $estimation->additional_notes,
                'status' => $data['status'] ?? $estimation->status,
            ]);

            // Process deleted products (cascades to items)
            if (!empty($data['deleted_product_ids']) && is_array($data['deleted_product_ids'])) {
                EstimationProduct::whereIn('id', $data['deleted_product_ids'])
                    ->where('estimation_id', $estimation->id)
                    ->delete();
            }

            // Update or create products & items
            if (isset($data['products'])) {
                $products = $this->upsertProductsWithItems($estimation, $data);
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
                $totalCft = $this->calculateTotalCft($estimation);
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
                $estimation->attachments()->delete();
                $attachments = $this->createAttachments($estimation->id, $data);
            }

            // Recalculate grand total
            $grandTotal = $this->calculateGrandTotal($estimation, $otherCharges);
            $estimation->update(['grand_total' => $grandTotal]);

            $estimation->load([
                'project',
                'customer',
                'products.product',
                'products.items',
                'otherCharge',
                'attachments',
            ]);

            return [
                'estimation' => $estimation,
                'products' => $products ?? $estimation->products,
                'other_charges' => $otherCharges,
                'grand_total' => $grandTotal,
                'attachments' => $attachments,
            ];
        });
    }

    // ─── Private Helpers ─────────────────────────────────────────────

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
     * Create products (basic) with their items (detailed).
     *
     * Expected input:
     * products: [
     *   {
     *     product_id: 1,
     *     items: [
     *       { length, breadth, thickness, unit_type, quantity, rate },
     *       ...
     *     ]
     *   },
     *   ...
     * ]
     *
     * @return \Illuminate\Support\Collection
     */
    private function createProductsWithItems(Estimation $estimation, array $data): \Illuminate\Support\Collection
    {
        $productsCollection = collect();

        if (empty($data['products'])) {
            return $productsCollection;
        }

        foreach ($data['products'] as $productData) {
            // Step 2: Create product (basic – only product_id)
            $product = EstimationProduct::create([
                'estimation_id' => $estimation->id,
                'org_id' => $data['org_id'] ?? $estimation->org_id,
                'company_id' => $data['company_id'] ?? $estimation->company_id,
                'product_id' => $productData['product_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? $estimation->customer_id,
                'project_id' => $data['project_id'] ?? $estimation->project_id,
                'total_amount' => 0, // will be aggregated from items
            ]);

            // Step 3: Create items for this product
            if (!empty($productData['items']) && is_array($productData['items'])) {
                $this->createItemsForProduct($product, $productData['items'], $estimation);
            }

            // Step 4: Aggregate items → product total
            $product->recalculateFromItems();

            $product->load(['product', 'items']);
            $productsCollection->push($product);
        }

        return $productsCollection;
    }

    /**
     * Create items for a specific estimation product.
     */
    private function createItemsForProduct(EstimationProduct $product, array $items, Estimation $estimation): void
    {
        foreach ($items as $itemData) {
            $item = EstimationProductsItem::create([
                'name' => $itemData['name'] ?? null,
                'org_id' => $product->org_id,
                'company_id' => $product->company_id,
                'estimation_product_id' => $product->id,
                'estimation_id' => $estimation->id,
                'product_id' => $product->product_id,
                'length' => $itemData['length'] ?? 0,
                'breadth' => $itemData['breadth'] ?? 0,
                'height' => $itemData['height'] ?? 0,
                'thickness' => $itemData['thickness'] ?? 0,
                'unit_type' => $itemData['unit_type'] ?? '1',
                'quantity' => $itemData['quantity'] ?? 1,
                'rate' => $itemData['rate'] ?? 0,
                'item_cft' => $itemData['item_cft'] ?? 0,
            ]);

            // Auto-calculate CFT and total
            $item->performCalculations();
            $item->save();
        }
    }

    /**
     * Upsert products and items during update.
     *
     * @return \Illuminate\Support\Collection
     */
    private function upsertProductsWithItems(Estimation $estimation, array $data): \Illuminate\Support\Collection
    {
        $productsCollection = collect();

        foreach ($data['products'] as $productData) {
            if (!empty($productData['id'])) {
                // Update existing product
                $product = EstimationProduct::where('id', $productData['id'])
                    ->where('estimation_id', $estimation->id)
                    ->firstOrFail();

                $product->update([
                    'product_id' => $productData['product_id'] ?? $product->product_id,
                ]);

                // Handle deleted items
                if (!empty($productData['deleted_item_ids'])) {
                    EstimationProductsItem::whereIn('id', $productData['deleted_item_ids'])
                        ->where('estimation_product_id', $product->id)
                        ->delete();
                }

                // Upsert items
                if (!empty($productData['items'])) {
                    $this->upsertItemsForProduct($product, $productData['items'], $estimation);
                }
            } else {
                // Create new product
                $product = EstimationProduct::create([
                    'estimation_id' => $estimation->id,
                    'org_id' => $data['org_id'] ?? $estimation->org_id,
                    'company_id' => $data['company_id'] ?? $estimation->company_id,
                    'product_id' => $productData['product_id'] ?? null,
                    'customer_id' => $data['customer_id'] ?? $estimation->customer_id,
                    'project_id' => $data['project_id'] ?? $estimation->project_id,
                    'total_amount' => 0,
                ]);

                // Create items
                if (!empty($productData['items'])) {
                    $this->createItemsForProduct($product, $productData['items'], $estimation);
                }
            }

            // Recalculate product total from items
            $product->recalculateFromItems();

            $product->load(['product', 'items']);
            $productsCollection->push($product);
        }

        return $productsCollection;
    }

    /**
     * Upsert items for an existing product.
     */
    private function upsertItemsForProduct(EstimationProduct $product, array $items, Estimation $estimation): void
    {
        foreach ($items as $itemData) {
            $attributes = [
                'name' => $itemData['name'] ?? null,
                'org_id' => $product->org_id,
                'company_id' => $product->company_id,
                'estimation_id' => $estimation->id,
                'product_id' => $product->product_id,
                'length' => $itemData['length'] ?? 0,
                'breadth' => $itemData['breadth'] ?? 0,
                'height' => $itemData['height'] ?? 0,
                'thickness' => $itemData['thickness'] ?? 0,
                'unit_type' => $itemData['unit_type'] ?? '1',
                'quantity' => $itemData['quantity'] ?? 1,
                'rate' => $itemData['rate'] ?? 0,
                'item_cft' => $itemData['item_cft'] ?? 0,
            ];

            if (!empty($itemData['id'])) {
                // Update existing item
                $item = EstimationProductsItem::where('id', $itemData['id'])
                    ->where('estimation_product_id', $product->id)
                    ->firstOrFail();
                $item->update($attributes);
            } else {
                // Create new item
                $attributes['estimation_product_id'] = $product->id;
                $item = EstimationProductsItem::create($attributes);
            }

            $item->performCalculations();
            $item->save();
        }
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

            $imagePath = null;
            $description = null;

            if (is_string($attachmentData)) {
                if (preg_match('/^data:image\/(\w+);base64,/i', $attachmentData, $matches)) {
                    $imagePath = $this->saveBase64Image($attachmentData, $estimationId);
                } else {
                    $imagePath = $attachmentData;
                }
            } elseif (is_array($attachmentData)) {
                $imagePath = $attachmentData['image'] ?? null;
                $description = $attachmentData['description'] ?? null;

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
        preg_match('/^data:image\/(\w+);base64,/i', $base64Data, $matches);
        $extension = $matches[1] ?? 'png';

        $base64Content = preg_replace('/^data:image\/\w+;base64,/i', '', $base64Data);
        $imageData = base64_decode($base64Content);

        if ($imageData === false) {
            throw new \Exception('Invalid base64 image data');
        }

        $directory = public_path("uploads/estimations/{$estimationId}");
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = time() . '_' . uniqid() . ".{$extension}";
        $filepath = "uploads/estimations/{$estimationId}/{$filename}";

        file_put_contents(public_path($filepath), $imageData);

        return $filepath;
    }

    /**
     * Calculate total CFT from all product items in the estimation.
     */
    private function calculateTotalCft(Estimation $estimation): float
    {
        return (float) EstimationProductsItem::whereIn(
            'estimation_product_id',
            $estimation->products()->pluck('id')
        )->selectRaw('SUM(item_cft * quantity) as total_cft')
         ->value('total_cft') ?? 0;
    }

    /**
     * Calculate grand total from product totals and other charges.
     */
    private function calculateGrandTotal(Estimation $estimation, $otherCharges): float
    {
        // Sum of all product totals (each product total is aggregated from its items)
        $productsTotal = (float) $estimation->products()->sum('total_amount');

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
            $estimation->delete();
        });
    }
}
