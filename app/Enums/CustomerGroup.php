<?php

namespace App\Enums;

enum CustomerGroup: string
{
    case Commercial = 'Commercial';
    case Government = 'Government';
    case NonProfit = 'Non Profit';
    case Individual = 'Individual';

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->name === 'NonProfit' ? 'Non Profit' : $case->name,
        ], self::cases());
    }
}
