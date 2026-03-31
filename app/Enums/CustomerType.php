<?php

namespace App\Enums;

enum CustomerType: string
{
    case Company = 'Company';
    case Individual = 'Individual';
    case Partnership = 'Partnership';

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->name,
        ], self::cases());
    }
}
