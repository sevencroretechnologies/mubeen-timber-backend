<?php

namespace App\Enums;

enum PriceList: string
{
    case StandardINR = 'Standard Selling INR';
    case StandardAED = 'Standard Selling UAE AED';
    case StandardUSD = 'Standard Selling USA USD';

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->value,
        ], self::cases());
    }
}
