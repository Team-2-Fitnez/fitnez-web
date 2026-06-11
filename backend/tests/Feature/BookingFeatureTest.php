<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\TrainerBooking;
use App\Models\TrainerDetail;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            'base_price' => 100_000,
        ]);
    }

    public function test_member_can_create_booking(): void
    {
        $this->authenticateAs($this->member);

        $response = $this->postJson('/api/bookings', [
            'trainer_id' => $this->trainer->id,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'sessions_per_week' => 3,
            'session_days' => ['monday', 'wednesday', 'friday'],
            'session_time' => '09:00',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'id', 'member_id', 'trainer_id', 'status',
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
            'start_date' => now()->addDay()->format('Y-m-d'),
            'sessions_per_week' => 3,
            'session_days' => ['monday', 'wednesday', 'friday'],
            'session_time' => '09:00',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->trainer->id,
            'notification_type' => 'booking_request',
        ]);
    }

    public function test_booking_conflict_detected(): void
    {
        $this->authenticateAs($this->member);

        $startDate = now()->addDay();
        $endDate = (clone $startDate)->modify('+4 weeks')->modify('-1 day');

        TrainerBooking::factory()->create([
            'trainer_id' => $this->trainer->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'session_days' => ['monday', 'wednesday', 'friday'],
            'session_time' => '09:00',
            'status' => TrainerBooking::STATUS_CONFIRMED,
        ]);

        $response = $this->postJson('/api/bookings', [
            'trainer_id' => $this->trainer->id,
            'start_date' => $startDate->toDateString(),
            'sessions_per_week' => 3,
            'session_days' => ['monday', 'wednesday', 'friday'],
            'session_time' => '09:00',
        ]);

        $response->assertStatus(422);
    }

    public function test_booking_allowed_on_same_day_different_time(): void
    {
        $this->authenticateAs($this->member);

        $startDate = now()->addDay();
        $endDate = (clone $startDate)->modify('+4 weeks')->modify('-1 day');

        TrainerBooking::factory()->create([
            'trainer_id' => $this->trainer->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'session_days' => ['monday', 'wednesday', 'friday'],
            'session_time' => '09:00',
            'status' => TrainerBooking::STATUS_CONFIRMED,
        ]);

        $response = $this->postJson('/api/bookings', [
            'trainer_id' => $this->trainer->id,
            'start_date' => $startDate->toDateString(),
            'sessions_per_week' => 3,
            'session_days' => ['monday', 'wednesday', 'friday'],
            'session_time' => '10:00',
        ]);

        $response->assertStatus(201);
    }

    public function test_booking_allowed_if_previous_booking_completed(): void
    {
        $this->authenticateAs($this->member);

        $startDate = now()->addDay();
        $endDate = (clone $startDate)->modify('+4 weeks')->modify('-1 day');

        TrainerBooking::factory()->create([
            'trainer_id' => $this->trainer->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'session_days' => ['monday', 'wednesday', 'friday'],
            'session_time' => '09:00',
            'status' => TrainerBooking::STATUS_COMPLETED,
        ]);

        $response = $this->postJson('/api/bookings', [
            'trainer_id' => $this->trainer->id,
            'start_date' => $startDate->toDateString(),
            'sessions_per_week' => 3,
            'session_days' => ['monday', 'wednesday', 'friday'],
            'session_time' => '09:00',
        ]);

        $response->assertStatus(201);
    }

    public function test_unauthenticated_user_cannot_book(): void
    {
        $response = $this->postJson('/api/bookings', [
            'trainer_id' => $this->trainer->id,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'sessions_per_week' => 3,
            'session_days' => ['monday', 'wednesday', 'friday'],
            'session_time' => '09:00',
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
        $admin = User::factory()->admin()->create();
        $this->authenticateAs($admin);

        $booking = TrainerBooking::factory()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
            'status' => TrainerBooking::STATUS_PENDING_PAYMENT,
        ]);

        $response = $this->postJson("/api/admin/bookings/{$booking->id}/confirm-payment");

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

    public function test_member_can_upload_payment_proof(): void
    {
        Storage::fake('public');
        $this->authenticateAs($this->member);

        $booking = TrainerBooking::factory()->pending()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
        ]);

        $file = UploadedFile::fake()->image('proof.jpg', 300, 400);

        $response = $this->postJson("/api/bookings/{$booking->id}/upload-proof", [
            'payment_proof' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data']);

        $this->assertDatabaseHas('trainer_bookings', [
            'id' => $booking->id,
            'status' => TrainerBooking::STATUS_PENDING_PAYMENT,
        ]);
    }

    public function test_admin_can_reject_payment(): void
    {
        $admin = User::factory()->admin()->create();
        $this->authenticateAs($admin);

        $booking = TrainerBooking::factory()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
            'status' => TrainerBooking::STATUS_PENDING_PAYMENT,
        ]);

        $response = $this->postJson("/api/admin/bookings/{$booking->id}/reject-payment", [
            'reason' => 'Bukti transfer tidak jelas.',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('trainer_bookings', [
            'id' => $booking->id,
            'status' => TrainerBooking::STATUS_CANCELLED,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->member->id,
            'notification_type' => 'payment_rejected',
        ]);
    }

    public function test_auto_complete_expired_bookings(): void
    {
        $admin = User::factory()->admin()->create();
        $this->authenticateAs($admin);

        $oldDate = now()->subDays(2)->format('Y-m-d');
        $olderDate = now()->subDays(35)->format('Y-m-d');

        TrainerBooking::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
            'start_date' => $olderDate,
            'end_date' => $oldDate,
        ]);

        $response = $this->postJson('/api/bookings/auto-complete');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message', 'data' => ['processed']]);

        $this->assertGreaterThan(0, $response->json('data.processed'));
    }
}
