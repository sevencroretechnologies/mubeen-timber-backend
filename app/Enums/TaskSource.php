<?php

namespace App\Enums;

enum TaskSource: int
{
    case LEAD = 1;
    case PROSPECT = 2;
    case OPPORTUNITY = 3;

    public function label(): string
    {
        return match ($this) {
            self::LEAD => 'Lead',
            self::PROSPECT => 'Prospect',
            self::OPPORTUNITY => 'Opportunity',
        };
    }
}
