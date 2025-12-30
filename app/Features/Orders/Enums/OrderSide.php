<?php

namespace App\Features\Orders\Enums;

enum OrderSide: string
{
    case BUY = 'buy';
    case SELL = 'sell';

    public function getLabel(): string
    {
        return match($this) {
            self::BUY => 'Buy',
            self::SELL => 'Sell',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::BUY => 'green',
            self::SELL => 'red',
        };
    }
}
