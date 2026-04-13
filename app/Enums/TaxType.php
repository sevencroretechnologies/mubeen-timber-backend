<?php

namespace App\Enums;

enum TaxType: string
{
    case SGST = 'SGST';
    case CGST = 'CGST';
    case IGST = 'IGST';
    case CESS = 'CESS';
    case FLOOD_CESS = 'FLOOD_CESS';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
