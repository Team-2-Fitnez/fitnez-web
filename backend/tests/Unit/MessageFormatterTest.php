<?php

namespace Tests\Unit;

use App\Services\Chat\RegularMessageFormatter;
use App\Services\Chat\SystemMessageFormatter;
use App\Services\Chat\NotificationMessageFormatter;
use App\Services\Chat\MessageFormatterFactory;
use PHPUnit\Framework\TestCase;

class MessageFormatterTest extends TestCase
{
    // ========================================================================
    //  STRATEGY PATTERN — RegularMessageFormatter
    // ========================================================================

    public function test_regular_formatter_trims_whitespace(): void
    {
        $formatter = new RegularMessageFormatter();

        $result = $formatter->format('  Hello, how are you?  ');

        $this->assertEquals('Hello, how are you?', $result);
    }

    public function test_regular_formatter_handles_empty_message(): void
    {
        $formatter = new RegularMessageFormatter();

        $result = $formatter->format('');

        $this->assertEquals('', $result);
    }

    public function test_regular_formatter_ignores_context(): void
    {
        $formatter = new RegularMessageFormatter();

        $result = $formatter->format('Test message', ['prefix' => '[System]', 'type' => 'booking']);

        $this->assertEquals('Test message', $result);
    }

    public function test_regular_formatter_preserves_message_without_spaces(): void
    {
        $formatter = new RegularMessageFormatter();

        $result = $formatter->format('Hello');

        $this->assertEquals('Hello', $result);
    }

    // ========================================================================
    //  STRATEGY PATTERN — SystemMessageFormatter
    // ========================================================================

    public function test_system_formatter_adds_default_prefix(): void
    {
        $formatter = new SystemMessageFormatter();

        $result = $formatter->format('Session started');

        $this->assertEquals('[System] Session started', $result);
    }

    public function test_system_formatter_uses_custom_prefix_from_context(): void
    {
        $formatter = new SystemMessageFormatter();

        $result = $formatter->format('Payment received', ['prefix' => '[Payment]']);

        $this->assertEquals('[Payment] Payment received', $result);
    }

    public function test_system_formatter_does_not_duplicate_prefix(): void
    {
        $formatter = new SystemMessageFormatter();

        $result = $formatter->format('[System] Session ended');

        $this->assertEquals('[System] Session ended', $result);
    }

    public function test_system_formatter_trims_message_before_adding_prefix(): void
    {
        $formatter = new SystemMessageFormatter();

        $result = $formatter->format('  System rebooting  ');

        $this->assertEquals('[System] System rebooting', $result);
    }

    public function test_system_formatter_with_different_prefix_no_duplicate(): void
    {
        $formatter = new SystemMessageFormatter();

        $result = $formatter->format('[Custom] Already prefixed', ['prefix' => '[Custom]']);

        $this->assertEquals('[Custom] Already prefixed', $result);
    }

    // ========================================================================
    //  STRATEGY PATTERN — NotificationMessageFormatter
    // ========================================================================

    public function test_notification_formatter_adds_default_emoji(): void
    {
        $formatter = new NotificationMessageFormatter();

        $result = $formatter->format('New update available');

        $this->assertEquals('🔔 New update available', $result);
    }

    public function test_notification_formatter_uses_payment_emoji(): void
    {
        $formatter = new NotificationMessageFormatter();

        $result = $formatter->format('Payment of Rp150,000 received', ['type' => 'payment']);

        $this->assertEquals('💰 Payment of Rp150,000 received', $result);
    }

    public function test_notification_formatter_uses_schedule_emoji(): void
    {
        $formatter = new NotificationMessageFormatter();

        $result = $formatter->format('Session rescheduled to 15:00', ['type' => 'schedule']);

        $this->assertEquals('📅 Session rescheduled to 15:00', $result);
    }

    public function test_notification_formatter_uses_booking_emoji(): void
    {
        $formatter = new NotificationMessageFormatter();

        $result = $formatter->format('New booking request', ['type' => 'booking']);

        $this->assertEquals('📋 New booking request', $result);
    }

    public function test_notification_formatter_trims_message(): void
    {
        $formatter = new NotificationMessageFormatter();

        $result = $formatter->format('  Reminder  ');

        $this->assertEquals('🔔 Reminder', $result);
    }

    // ========================================================================
    //  FACTORY PATTERN — MessageFormatterFactory
    // ========================================================================

    public function test_factory_creates_regular_formatter_by_default(): void
    {
        $formatter = MessageFormatterFactory::create();

        $this->assertInstanceOf(RegularMessageFormatter::class, $formatter);
    }

    public function test_factory_creates_regular_formatter_explicitly(): void
    {
        $formatter = MessageFormatterFactory::create('regular');

        $this->assertInstanceOf(RegularMessageFormatter::class, $formatter);
        $this->assertEquals('Hi', $formatter->format('Hi'));
    }

    public function test_factory_creates_system_formatter(): void
    {
        $formatter = MessageFormatterFactory::create('system');

        $this->assertInstanceOf(SystemMessageFormatter::class, $formatter);
        $this->assertEquals('[System] Hello', $formatter->format('Hello'));
    }

    public function test_factory_creates_notification_formatter(): void
    {
        $formatter = MessageFormatterFactory::create('notification');

        $this->assertInstanceOf(NotificationMessageFormatter::class, $formatter);
        $this->assertEquals('🔔 Alert', $formatter->format('Alert'));
    }

    public function test_factory_defaults_to_regular_for_unknown_type(): void
    {
        $formatter = MessageFormatterFactory::create('urgent');

        $this->assertInstanceOf(RegularMessageFormatter::class, $formatter);
    }

    // ========================================================================
    //  OPEN/CLOSED PRINCIPLE DEMONSTRATION
    // ========================================================================

    public function test_all_formatters_comply_with_interface(): void
    {
        $formatters = [
            new RegularMessageFormatter(),
            new SystemMessageFormatter(),
            new NotificationMessageFormatter(),
        ];

        foreach ($formatters as $formatter) {
            $result = $formatter->format('Test message');
            $this->assertIsString($result);
            $this->assertNotEmpty($result);
        }
    }
}
