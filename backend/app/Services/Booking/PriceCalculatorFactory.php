<?php

namespace App\Services\Booking;

class PriceCalculatorFactory
{
    public static function create(string $sessionType): PriceCalculator
    {
        return match ($sessionType) {
            'online'  => new OnlinePriceCalculator(),
            'offline' => new OfflinePriceCalculator(),
            'promo'   => new PromoPriceCalculator(),
            default   => new OnlinePriceCalculator(),
        };
    }
}
