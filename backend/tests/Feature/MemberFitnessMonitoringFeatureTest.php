<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TrainerBooking;
use App\Models\TrainerDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\WithJwtAuth;
use Tests\TestCase;

class MemberFitnessMonitoringFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithJwtAuth;

    private User $trainer;
    private User $member;
    private Role $memberRole;
    private Role $trainerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->memberRole = Role::factory()->member()->create();
        $this->trainerRole = Role::factory()->trainer()->create();

        $this->trainer = User::factory()->create([
            'role_id' => $this->trainerRole->id,
        ]);
        
        TrainerDetail::factory()->create([
            'user_id' => $this->trainer->id,
        ]);

        $this->member = User::factory()->create([
            'role_id' => $this->memberRole->id,
        ]);
    }

    private function getLocalNow()
    {
        $tz = config('app.timezone') === 'UTC' ? 'Asia/Jakarta' : config('app.timezone');
        return now($tz);
    }

    public function test_trainer_can_see_member_with_active_booking(): void
    {
        $this->authenticateAs($this->trainer);
        $now = $this->getLocalNow();

        TrainerBooking::factory()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
            'booking_date' => $now->toDateString(),
            'start_time' => $now->copy()->subMinutes(10)->format('H:i'),
            'end_time' => $now->copy()->addMinutes(30)->format('H:i'),
            'status' => TrainerBooking::STATUS_CONFIRMED,
        ]);

        // Summary
        $response = $this->getJson('/api/trainer/member-monitoring/summary');
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total_members'));

        // List members
        $response = $this->getJson('/api/trainer/member-monitoring/members');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));

        // Show details
        $response = $this->getJson("/api/trainer/member-monitoring/members/{$this->member->id}");
        $response->assertStatus(200);
    }

    public function test_trainer_cannot_see_member_with_completed_booking(): void
    {
        $this->authenticateAs($this->trainer);
        $now = $this->getLocalNow();

        TrainerBooking::factory()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
            'booking_date' => $now->toDateString(),
            'start_time' => $now->copy()->subMinutes(60)->format('H:i'),
            'end_time' => $now->copy()->subMinutes(10)->format('H:i'),
            'status' => TrainerBooking::STATUS_COMPLETED,
        ]);

        // Summary
        $response = $this->getJson('/api/trainer/member-monitoring/summary');
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('data.total_members'));

        // List members
        $response = $this->getJson('/api/trainer/member-monitoring/members');
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.data'));

        // Show details
        $response = $this->getJson("/api/trainer/member-monitoring/members/{$this->member->id}");
        $response->assertStatus(403);
    }

    public function test_trainer_cannot_see_member_with_expired_booking(): void
    {
        $this->authenticateAs($this->trainer);
        $now = $this->getLocalNow();

        // A booking that ended 65 minutes ago (more than 1 hour ago)
        TrainerBooking::factory()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
            'booking_date' => $now->toDateString(),
            'start_time' => $now->copy()->subMinutes(120)->format('H:i'),
            'end_time' => $now->copy()->subMinutes(65)->format('H:i'),
            'status' => TrainerBooking::STATUS_CONFIRMED,
        ]);

        // Summary
        $response = $this->getJson('/api/trainer/member-monitoring/summary');
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('data.total_members'));

        // List members
        $response = $this->getJson('/api/trainer/member-monitoring/members');
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.data'));

        // Show details
        $response = $this->getJson("/api/trainer/member-monitoring/members/{$this->member->id}");
        $response->assertStatus(403);
    }

    public function test_trainer_cannot_see_other_trainers_member(): void
    {
        $otherTrainer = User::factory()->create([
            'role_id' => $this->trainerRole->id,
        ]);
        TrainerDetail::factory()->create([
            'user_id' => $otherTrainer->id,
        ]);

        // Authenticate as other trainer (who has no active bookings with our member)
        $this->authenticateAs($otherTrainer);
        $now = $this->getLocalNow();

        TrainerBooking::factory()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id, // booked with the first trainer
            'booking_date' => $now->toDateString(),
            'start_time' => $now->copy()->subMinutes(10)->format('H:i'),
            'end_time' => $now->copy()->addMinutes(30)->format('H:i'),
            'status' => TrainerBooking::STATUS_CONFIRMED,
        ]);

        // Summary for otherTrainer
        $response = $this->getJson('/api/trainer/member-monitoring/summary');
        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('data.total_members'));

        // List members for otherTrainer
        $response = $this->getJson('/api/trainer/member-monitoring/members');
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.data'));

        // Show details for otherTrainer
        $response = $this->getJson("/api/trainer/member-monitoring/members/{$this->member->id}");
        $response->assertStatus(403);
    }
}
