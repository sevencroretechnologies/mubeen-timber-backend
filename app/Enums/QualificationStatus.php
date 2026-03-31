<?php

namespace App\Enums;

enum QualificationStatus: string
{
    case QUALIFIED = 'Qualified';
    case UNQUALIFIED = 'Unqualified';
    case IN_PROGRESS = 'In Progress';

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get enum options for dropdowns.
     */
    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->value,
        ], self::cases());
    }
}
