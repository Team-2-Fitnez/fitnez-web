<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TrainerApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\WithJwtAuth;
use Tests\TestCase;

class TrainerApplicationFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithJwtAuth;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Role::factory()->member()->create();
        Role::factory()->admin()->create();

        $this->member = User::factory()->member()->create();
    }

    public function test_member_can_submit_application(): void
    {
        Storage::fake('local');
        $this->authenticateAs($this->member);

        $cv = UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf');
        $certificate = UploadedFile::fake()->create('cert.pdf', 200, 'application/pdf');

        $response = $this->postJson('/api/trainer/application', [
            'specialization' => 'Strength Training',
            'experience_years' => 5,
            'cv' => $cv,
            'certificate' => $certificate,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'message', 'data',
            ]);

        $this->assertDatabaseHas('trainer_applications', [
            'user_id' => $this->member->id,
            'status' => 'pending',
        ]);
    }

    public function test_duplicate_application_is_blocked(): void
    {
        Storage::fake('local');
        $this->authenticateAs($this->member);

        TrainerApplication::factory()->pending()->create([
            'user_id' => $this->member->id,
        ]);

        $cv = UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf');
        $certificate = UploadedFile::fake()->create('cert.pdf', 200, 'application/pdf');

        $response = $this->postJson('/api/trainer/application', [
            'specialization' => 'Yoga',
            'experience_years' => 3,
            'cv' => $cv,
            'certificate' => $certificate,
        ]);

        $response->assertStatus(422);
    }

    public function test_member_can_check_application_status(): void
    {
        $this->authenticateAs($this->member);

        TrainerApplication::factory()->pending()->create([
            'user_id' => $this->member->id,
        ]);

        $response = $this->getJson('/api/trainer/application');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'application', 'status', 'can_access_trainer_workspace',
                ],
            ]);

        $this->assertEquals('pending', $response->json('data.status'));
    }

    public function test_status_shows_no_application_when_none_submitted(): void
    {
        $this->authenticateAs($this->member);

        $response = $this->getJson('/api/trainer/application');

        $response->assertStatus(200);
        $this->assertNull($response->json('data.application'));
        $this->assertFalse($response->json('data.can_access_trainer_workspace'));
    }
}
