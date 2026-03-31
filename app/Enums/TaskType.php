<?php

namespace App\Enums;

enum TaskType: int
{
    case CALL = 1;
    case EMAIL = 2;
    case WHATSAPP = 3;

    public function label(): string
    {
        return match ($this) {
            self::CALL => 'Call',
            self::EMAIL => 'Email',
            self::WHATSAPP => 'WhatsApp',
        };
    }
}
