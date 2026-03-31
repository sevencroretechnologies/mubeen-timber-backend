<?php

namespace App\Enums;

enum PaymentTerm: string
{
    case Immediate = 'Immediate';
    case Net15 = 'Net 15';
    case Net30 = 'Net 30';
    case Net45 = 'Net 45';
    case Net60 = 'Net 60';

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->value,
        ], self::cases());
    }
}
