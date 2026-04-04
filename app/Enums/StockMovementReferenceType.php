<?php

namespace App\Enums;

enum StockMovementReferenceType: string
{
    case PurchaseOrder = 'purchase_order';
    case JobCard = 'job_card';
    case MaterialRequisition = 'material_requisition';
    case Manual = 'manual';
    case EstimationCollection = 'estimation_collection';

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->name,
        ], self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::PurchaseOrder => 'Purchase Order',
            self::JobCard => 'Job Card',
            self::MaterialRequisition => 'Material Requisition',
            self::Manual => 'Manual',
            self::EstimationCollection => 'Estimation Collection',
        };
    }
}
