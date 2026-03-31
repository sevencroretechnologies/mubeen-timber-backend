<?php

namespace App\Enums;

enum DocumentOwnerType: string
{
    case Employee = 'employee';
    case Company = 'company';
    case Accountant = 'accountant';

    public function label(): string
    {
        return match($this) {
            self::Employee => 'Employee',
            self::Company => 'Company',
            self::Accountant => 'Accountant',
        };
    }

    public function id(): int
    {
        return match($this) {
            self::Employee => 1,
            self::Company => 2,
            self::Accountant => 3,
        };
    }
}
