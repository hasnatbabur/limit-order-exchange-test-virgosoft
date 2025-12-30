<?php

namespace App\Features\Orders\Enums;

enum OrderStatus: string
{
    case OPEN = 'open';
    case FILLED = 'filled';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match($this) {
            self::OPEN => 'Open',
            self::FILLED => 'Filled',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::OPEN => 'yellow',
            self::FILLED => 'green',
            self::CANCELLED => 'red',
        };
    }

    public function canBeCancelled(): bool
    {
        return $this === self::OPEN;
    }

    public function canBeFilled(): bool
    {
        return $this === self::OPEN;
    }
}
