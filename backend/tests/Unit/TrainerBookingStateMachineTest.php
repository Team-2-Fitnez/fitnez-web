<?php

namespace Tests\Unit;

use App\Models\TrainerBooking;
use PHPUnit\Framework\TestCase;

class TrainerBookingStateMachineTest extends TestCase
{
    private TrainerBooking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->booking = new TrainerBooking();
    }

    // ========================================================================
    //  STATE MACHINE PATTERN — Valid transitions from PENDING
    // ========================================================================

    public function test_pending_can_transition_to_confirmed(): void
    {
        $this->booking->status = TrainerBooking::STATUS_PENDING;

        $this->assertTrue($this->booking->canTransitionTo(TrainerBooking::STATUS_CONFIRMED));
    }

    public function test_pending_can_transition_to_cancelled(): void
    {
        $this->booking->status = TrainerBooking::STATUS_PENDING;

        $this->assertTrue($this->booking->canTransitionTo(TrainerBooking::STATUS_CANCELLED));
    }

    public function test_pending_can_transition_to_rejected(): void
    {
        $this->booking->status = TrainerBooking::STATUS_PENDING;

        $this->assertTrue($this->booking->canTransitionTo(TrainerBooking::STATUS_REJECTED));
    }

    public function test_pending_cannot_transition_to_completed(): void
    {
        $this->booking->status = TrainerBooking::STATUS_PENDING;

        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_COMPLETED));
    }

    // ========================================================================
    //  STATE MACHINE PATTERN — Valid transitions from CONFIRMED
    // ========================================================================

    public function test_confirmed_can_transition_to_completed(): void
    {
        $this->booking->status = TrainerBooking::STATUS_CONFIRMED;

        $this->assertTrue($this->booking->canTransitionTo(TrainerBooking::STATUS_COMPLETED));
    }

    public function test_confirmed_can_transition_to_cancelled(): void
    {
        $this->booking->status = TrainerBooking::STATUS_CONFIRMED;

        $this->assertTrue($this->booking->canTransitionTo(TrainerBooking::STATUS_CANCELLED));
    }

    public function test_confirmed_cannot_transition_to_pending(): void
    {
        $this->booking->status = TrainerBooking::STATUS_CONFIRMED;

        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_PENDING));
    }

    public function test_confirmed_cannot_transition_to_rejected(): void
    {
        $this->booking->status = TrainerBooking::STATUS_CONFIRMED;

        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_REJECTED));
    }

    // ========================================================================
    //  STATE MACHINE PATTERN — Terminal states (completed, cancelled, rejected)
    // ========================================================================

    public function test_completed_cannot_transition_to_anything(): void
    {
        $this->booking->status = TrainerBooking::STATUS_COMPLETED;

        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_PENDING));
        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_CONFIRMED));
        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_CANCELLED));
        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_REJECTED));
    }

    public function test_cancelled_cannot_transition_to_anything(): void
    {
        $this->booking->status = TrainerBooking::STATUS_CANCELLED;

        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_PENDING));
        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_CONFIRMED));
        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_COMPLETED));
        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_REJECTED));
    }

    public function test_rejected_cannot_transition_to_anything(): void
    {
        $this->booking->status = TrainerBooking::STATUS_REJECTED;

        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_PENDING));
        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_CONFIRMED));
        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_COMPLETED));
        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_CANCELLED));
    }

    // ========================================================================
    //  STATE MACHINE PATTERN — Invalid/unknown status
    // ========================================================================

    public function test_unknown_status_returns_false(): void
    {
        $this->booking->status = 'unknown_status';

        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_CONFIRMED));
    }

    public function test_transition_to_unknown_status_returns_false(): void
    {
        $this->booking->status = TrainerBooking::STATUS_PENDING;

        $this->assertFalse($this->booking->canTransitionTo('invalid_status'));
    }

    public function test_transition_to_same_status_returns_false(): void
    {
        $this->booking->status = TrainerBooking::STATUS_PENDING;

        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_PENDING));
    }

    // ========================================================================
    //  DEMONSTRATION: Complete valid lifecycle
    // ========================================================================

    public function test_complete_booking_lifecycle(): void
    {
        // Fresh booking starts as pending
        $this->booking->status = TrainerBooking::STATUS_PENDING;
        $this->assertTrue($this->booking->canTransitionTo(TrainerBooking::STATUS_CONFIRMED));

        // Trainer confirms
        $this->booking->status = TrainerBooking::STATUS_CONFIRMED;
        $this->assertTrue($this->booking->canTransitionTo(TrainerBooking::STATUS_COMPLETED));

        // Session completed
        $this->booking->status = TrainerBooking::STATUS_COMPLETED;
        // Terminal — no further transitions
        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_CANCELLED));
        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_PENDING));
    }

    public function test_cancelled_booking_lifecycle(): void
    {
        $this->booking->status = TrainerBooking::STATUS_PENDING;
        $this->assertTrue($this->booking->canTransitionTo(TrainerBooking::STATUS_CANCELLED));

        $this->booking->status = TrainerBooking::STATUS_CANCELLED;
        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_CONFIRMED));
    }

    public function test_rejected_booking_lifecycle(): void
    {
        $this->booking->status = TrainerBooking::STATUS_PENDING;
        $this->assertTrue($this->booking->canTransitionTo(TrainerBooking::STATUS_REJECTED));

        $this->booking->status = TrainerBooking::STATUS_REJECTED;
        $this->assertFalse($this->booking->canTransitionTo(TrainerBooking::STATUS_CONFIRMED));
    }
}
