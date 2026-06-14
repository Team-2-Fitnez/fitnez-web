<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Payment;
use App\Models\TrainerBooking;
use App\Models\TrainerEarning;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\WithJwtAuth;
use Tests\TestCase;

class PaymentAttendanceReportFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithJwtAuth;

    private User $member;
    private User $trainer;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::factory()->member()->create();
        Role::factory()->admin()->create();
        Role::factory()->trainer()->create();

        $this->member = User::factory()->member()->create();
        $this->trainer = User::factory()->trainer()->create();
        $this->admin = User::factory()->admin()->create();
    }

    // ─── Attendance ───────────────────────────────────────────────────

    public function test_member_can_check_in(): void
    {
        $this->authenticateAs($this->member);

        $response = $this->postJson('/api/attendance/check-in', [
            'attendance_type' => 'member_checkin',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'message', 'data' => ['id', 'check_in_time']]);

        $this->assertDatabaseHas('attendance', [
            'user_id' => $this->member->id,
            'attendance_type' => 'member_checkin',
        ]);
    }

    public function test_member_can_check_out(): void
    {
        $this->authenticateAs($this->member);

        $this->postJson('/api/attendance/check-in', [
            'attendance_type' => 'member_checkin',
        ]);

        $response = $this->postJson('/api/attendance/check-out');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('attendance', [
            'user_id' => $this->member->id,
            'check_out_time' => null,
        ]);
    }

    public function test_member_can_view_attendance_history(): void
    {
        $this->authenticateAs($this->member);
        Attendance::factory()->count(3)->create([
            'user_id' => $this->member->id,
            'attendance_type' => 'member_checkin',
        ]);

        $response = $this->getJson('/api/attendance/history');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    // ─── Payments (Member) ────────────────────────────────────────────

    public function test_member_can_list_own_payments(): void
    {
        $this->authenticateAs($this->member);
        Payment::factory()->count(2)->create([
            'user_id' => $this->member->id,
        ]);

        $response = $this->getJson('/api/member/payments');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_member_can_view_payment_summary(): void
    {
        $this->authenticateAs($this->member);
        Payment::factory()->confirmed()->create([
            'user_id' => $this->member->id,
            'amount' => 100000,
        ]);
        Payment::factory()->pending()->create([
            'user_id' => $this->member->id,
            'amount' => 50000,
        ]);

        $response = $this->getJson('/api/member/payments/summary');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    // ─── Admin Reports ────────────────────────────────────────────────

    public function test_admin_can_view_report_summary(): void
    {
        $this->authenticateAs($this->admin);

        Attendance::factory()->count(3)->create(['attendance_type' => 'member_checkin']);
        Payment::factory()->confirmed()->count(2)->create();

        $response = $this->getJson('/api/admin/member-reports/summary');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => [
                'total_payments',
                'total_payments_trend',
                'total_payment_amount',
                'total_payment_amount_trend',
                'paid_payments',
                'pending_payments',
                'total_attendance',
                'attendance_today',
                'attendance_today_trend',
                'attendance_this_month',
            ]]);
    }

    public function test_admin_can_view_payments_report(): void
    {
        $this->authenticateAs($this->admin);
        Payment::factory()->count(5)->create();

        $response = $this->getJson('/api/admin/member-reports/payments');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['data', 'current_page', 'last_page']]);
    }

    public function test_admin_can_view_attendance_report(): void
    {
        $this->authenticateAs($this->admin);
        Attendance::factory()->count(5)->create();

        $response = $this->getJson('/api/admin/member-reports/attendance');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['data', 'current_page', 'last_page']]);
    }

    public function test_non_admin_cannot_access_admin_reports(): void
    {
        $this->authenticateAs($this->member);

        $response = $this->getJson('/api/admin/member-reports/summary');

        $response->assertStatus(403);
    }

    // ─── Trainer Income ───────────────────────────────────────────────

    public function test_trainer_can_view_income_summary(): void
    {
        $this->authenticateAs($this->trainer);

        TrainerEarning::factory()->disbursed()->create([
            'trainer_id' => $this->trainer->id,
            'trainer_amount' => 100000,
        ]);
        TrainerEarning::factory()->pending()->create([
            'trainer_id' => $this->trainer->id,
            'trainer_amount' => 50000,
        ]);

        $response = $this->getJson('/api/trainer/incoming-rent-history/summary');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_trainer_can_view_rent_history(): void
    {
        $this->authenticateAs($this->trainer);

        TrainerEarning::factory()->count(3)->create([
            'trainer_id' => $this->trainer->id,
        ]);

        $response = $this->getJson('/api/trainer/incoming-rent-history');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    // ─── Trainer Rent Breakdown ──────────────────────────────────────

    public function test_trainer_can_view_rent_breakdown(): void
    {
        $this->authenticateAs($this->trainer);

        TrainerEarning::factory()->disbursed()->create([
            'trainer_id' => $this->trainer->id,
            'trainer_amount' => 200000,
            'status' => 'paid',
        ]);

        TrainerEarning::factory()->pending()->create([
            'trainer_id' => $this->trainer->id,
            'trainer_amount' => 150000,
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/trainer/incoming-rent-history/breakdown');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    // ─── Admin Export ─────────────────────────────────────────────────

    public function test_admin_can_export_member_reports(): void
    {
        $this->authenticateAs($this->admin);

        Attendance::factory()->count(2)->create();
        Payment::factory()->confirmed()->count(2)->create();

        $response = $this->get('/api/admin/export/member-reports');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // ─── Unauthenticated ──────────────────────────────────────────────

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/attendance/history');
        $response->assertStatus(401);

        $response = $this->getJson('/api/member/payments');
        $response->assertStatus(401);

        $response = $this->getJson('/api/admin/member-reports/summary');
        $response->assertStatus(401);
    }
}
