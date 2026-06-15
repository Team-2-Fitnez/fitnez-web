<?php

namespace Tests\Unit;

use App\Features\Anggota4TrainerBookingScheduleChatPaymentsRent\Services\Booking\OnlinePriceCalculator;
use App\Features\Anggota4TrainerBookingScheduleChatPaymentsRent\Services\Booking\OfflinePriceCalculator;
use App\Features\Anggota4TrainerBookingScheduleChatPaymentsRent\Services\Booking\PromoPriceCalculator;
use App\Features\Anggota4TrainerBookingScheduleChatPaymentsRent\Services\Booking\PriceCalculatorFactory;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    // ========================================================================
    //  STRATEGY PATTERN — Each concrete strategy tested independently
    // ========================================================================

    public function test_online_price_calculator_applies_10_percent_discount(): void
    {
        $calculator = new OnlinePriceCalculator();

        $price = $calculator->calculate(100_000, 2.0);

        $this->assertEquals(180_000, $price);
    }

    public function test_online_price_calculator_with_fractional_hours(): void
    {
        $calculator = new OnlinePriceCalculator();

        $price = $calculator->calculate(100_000, 1.5);

        $this->assertEquals(135_000, $price);
    }

    public function test_offline_price_calculator_charges_full_rate(): void
    {
        $calculator = new OfflinePriceCalculator();

        $price = $calculator->calculate(100_000, 2.0);

        $this->assertEquals(200_000, $price);
    }

    public function test_offline_price_calculator_with_fractional_hours(): void
    {
        $calculator = new OfflinePriceCalculator();

        $price = $calculator->calculate(150_000, 1.5);

        $this->assertEquals(225_000, $price);
    }

    public function test_promo_price_calculator_applies_20_percent_discount(): void
    {
        $calculator = new PromoPriceCalculator();

        $price = $calculator->calculate(100_000, 2.0);

        $this->assertEquals(160_000, $price);
    }

    public function test_promo_price_calculator_with_fractional_hours(): void
    {
        $calculator = new PromoPriceCalculator();

        $price = $calculator->calculate(200_000, 0.5);

        $this->assertEquals(80_000, $price);
    }

    // ========================================================================
    //  FACTORY PATTERN  — Factory produces the correct strategy
    // ========================================================================

    public function test_factory_creates_online_calculator(): void
    {
        $calculator = PriceCalculatorFactory::create('online');

        $this->assertInstanceOf(OnlinePriceCalculator::class, $calculator);
        $this->assertEquals(90_000, $calculator->calculate(100_000, 1.0));
    }

    public function test_factory_creates_offline_calculator(): void
    {
        $calculator = PriceCalculatorFactory::create('offline');

        $this->assertInstanceOf(OfflinePriceCalculator::class, $calculator);
        $this->assertEquals(100_000, $calculator->calculate(100_000, 1.0));
    }

    public function test_factory_creates_promo_calculator(): void
    {
        $calculator = PriceCalculatorFactory::create('promo');

        $this->assertInstanceOf(PromoPriceCalculator::class, $calculator);
        $this->assertEquals(80_000, $calculator->calculate(100_000, 1.0));
    }

    public function test_factory_defaults_to_online_for_unknown_type(): void
    {
        $calculator = PriceCalculatorFactory::create('premium');

        $this->assertInstanceOf(OnlinePriceCalculator::class, $calculator);
    }

    // ========================================================================
    //  OPEN/CLOSED PRINCIPLE DEMONSTRATION
    //  Adding a new strategy does not require modifying existing code.
    //  These tests prove that existing strategies are unaffected by addition.
    // ========================================================================

    public function test_all_strategies_comply_with_interface(): void
    {
        $strategies = [
            new OnlinePriceCalculator(),
            new OfflinePriceCalculator(),
            new PromoPriceCalculator(),
        ];

        foreach ($strategies as $strategy) {
            $result = $strategy->calculate(50_000, 1.0);
            $this->assertIsFloat($result);
            $this->assertGreaterThan(0, $result);
        }
    }

    public function test_factory_returns_nothing_but_price_calculators(): void
    {
        $types = ['online', 'offline', 'promo'];

        foreach ($types as $type) {
            $instance = PriceCalculatorFactory::create($type);
            $this->assertInstanceOf(
                \App\Features\Anggota4TrainerBookingScheduleChatPaymentsRent\Services\Booking\PriceCalculator::class,
                $instance
            );
        }
    }
}
