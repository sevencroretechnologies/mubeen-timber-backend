<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case ORDERED = 'ordered';
    case PARTIAL_RECEIVED = 'partial_received';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

     public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}

  