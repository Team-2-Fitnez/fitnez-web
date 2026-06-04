<?php

namespace Tests\Feature;

use App\Models\TrainerBooking;
use App\Models\TrainerDetail;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\WithJwtAuth;
use Tests\TestCase;

class BookingFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithJwtAuth;

    private User $member;
    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::factory()->member()->create();
        Role::factory()->admin()->create();

        $this->member = User::factory()->member()->create();
        $this->trainer = User::factory()->member()->create();

        TrainerDetail::factory()->create([
            'user_id' => $this->trainer->id,
            'hourly_rate' => 100_000,
        ]);
    }

    public function test_member_can_create_booking(): void
    {
        $this->authenticateAs($this->member);

        $response = $this->postJson('/api/bookings', [
            'trainer_id' => $this->trainer->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'session_type' => 'online',
            'total_price' => 90_000,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'id', 'member_id', 'trainer_id', 'status', 'total_price',
                ],
            ]);

        $this->assertDatabaseHas('trainer_bookings', [
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
            'status' => TrainerBooking::STATUS_PENDING,
        ]);
    }

    public function test_booking_creates_notification_for_trainer(): void
    {
        $this->authenticateAs($this->member);

        $this->postJson('/api/bookings', [
            'trainer_id' => $this->trainer->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'session_type' => 'online',
            'total_price' => 90_000,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->trainer->id,
            'notification_type' => 'booking_request',
        ]);
    }

    public function test_booking_conflict_detected(): void
    {
        $this->authenticateAs($this->member);

        $date = now()->addDay()->format('Y-m-d');

        TrainerBooking::factory()->create([
            'trainer_id' => $this->trainer->id,
            'booking_date' => $date,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => TrainerBooking::STATUS_CONFIRMED,
        ]);

        $response = $this->postJson('/api/bookings', [
            'trainer_id' => $this->trainer->id,
            'booking_date' => $date,
            'start_time' => '09:30',
            'end_time' => '10:30',
            'session_type' => 'online',
            'total_price' => 90_000,
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_book(): void
    {
        $response = $this->postJson('/api/bookings', [
            'trainer_id' => $this->trainer->id,
            'booking_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'session_type' => 'online',
        ]);

        $response->assertStatus(401);
    }

    public function test_member_can_list_own_bookings(): void
    {
        $this->authenticateAs($this->member);

        TrainerBooking::factory()->count(3)->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
        ]);

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'message', 'data' => ['data', 'current_page'],
            ]);
    }

    public function test_valid_status_transition_from_pending_to_confirmed(): void
    {
        $this->authenticateAs($this->trainer);

        $booking = TrainerBooking::factory()->pending()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
        ]);

        $response = $this->patchJson("/api/bookings/{$booking->id}/status", [
            'status' => TrainerBooking::STATUS_CONFIRMED,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('trainer_bookings', [
            'id' => $booking->id,
            'status' => TrainerBooking::STATUS_CONFIRMED,
        ]);
    }

    public function test_invalid_status_transition_returns_error(): void
    {
        $this->authenticateAs($this->trainer);

        $booking = TrainerBooking::factory()->pending()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
        ]);

        $response = $this->patchJson("/api/bookings/{$booking->id}/status", [
            'status' => TrainerBooking::STATUS_COMPLETED,
        ]);

        $response->assertStatus(422);
    }
}
