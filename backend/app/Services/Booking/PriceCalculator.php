<?php

namespace App\Services\Booking;

interface PriceCalculator
{
    public function calculate(float $basePrice, float $durationHours, ?string $location = null): float;
}

class OnlinePriceCalculator implements PriceCalculator
{
    public function calculate(float $basePrice, float $durationHours, ?string $location = null): float
    {
        return $basePrice * $durationHours * 0.9;
    }
}

class OfflinePriceCalculator implements PriceCalculator
{
    public function calculate(float $basePrice, float $durationHours, ?string $location = null): float
    {
        return $basePrice * $durationHours * 1.0;
    }
}

class PromoPriceCalculator implements PriceCalculator
{
    public function calculate(float $basePrice, float $durationHours, ?string $location = null): float
    {
        return $basePrice * $durationHours * 0.8;
    }
}

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
