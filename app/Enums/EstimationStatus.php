<?php

namespace App\Enums;

enum EstimationStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Pending = 'pending';
    case Collected = 'collected';
    case Cancelled = 'cancelled';


    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->name,
        ], self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Approved => 'Approved',
            self::Pending => 'Pending',
            self::Collected => 'Collected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Approved => 'blue',
            self::Pending => 'yellow',
            self::Collected => 'green',
            self::Cancelled => 'red',
        };
    }

    public function canBeApproved(): bool
    {
        return $this === self::Draft;
    }

    public function canCollectMaterial(): bool
    {
        return in_array($this, [self::Approved, self::Pending]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::Draft, self::Approved, self::Pending]);
    }

    public function canBeEdited(): bool
    {
        return $this === self::Draft;
    }

    public function canBeDeleted(): bool
    {
        return $this === self::Draft;
    }
}
