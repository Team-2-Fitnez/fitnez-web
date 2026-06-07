<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\WithJwtAuth;
use Tests\TestCase;

class NotificationFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithJwtAuth;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Role::factory()->member()->create();

        $this->member = User::factory()->member()->create();
    }

    public function test_user_can_list_notifications(): void
    {
        $this->authenticateAs($this->member);

        Notification::factory()->count(5)->create([
            'user_id' => $this->member->id,
        ]);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'data', 'current_page',
                ],
            ]);
    }

    public function test_user_can_check_unread_count(): void
    {
        $this->authenticateAs($this->member);

        Notification::factory()->count(3)->unread()->create([
            'user_id' => $this->member->id,
        ]);
        Notification::factory()->count(2)->read()->create([
            'user_id' => $this->member->id,
        ]);

        $response = $this->getJson('/api/notifications/unread-count');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('data.count'));
    }

    public function test_user_can_mark_single_notification_as_read(): void
    {
        $this->authenticateAs($this->member);

        $notif = Notification::factory()->unread()->create([
            'user_id' => $this->member->id,
        ]);

        $response = $this->patchJson("/api/notifications/{$notif->id}/read");

        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => $notif->id,
            'is_read' => true,
        ]);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $this->authenticateAs($this->member);

        Notification::factory()->count(5)->unread()->create([
            'user_id' => $this->member->id,
        ]);

        $response = $this->patchJson('/api/notifications/read-all');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->member->id,
            'is_read' => false,
        ]);
    }

    public function test_cannot_mark_other_users_notification_as_read(): void
    {
        $this->authenticateAs($this->member);

        $otherUser = User::factory()->member()->create();

        $notif = Notification::factory()->unread()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->patchJson("/api/notifications/{$notif->id}/read");

        $response->assertStatus(403);
    }
}
