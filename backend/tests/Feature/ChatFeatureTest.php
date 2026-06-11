<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Role;
use App\Models\TrainerBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\WithJwtAuth;
use Tests\TestCase;

class ChatFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithJwtAuth;

    private User $member;
    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::factory()->member()->create();

        $this->member = User::factory()->member()->create();
        $this->trainer = User::factory()->member()->create();
    }

    public function test_member_can_send_message_to_trainer(): void
    {
        $this->authenticateAs($this->member);

        TrainerBooking::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
        ]);

        $response = $this->postJson('/api/chat/messages', [
            'receiver_id' => $this->trainer->id,
            'message' => 'Hello, I would like to discuss my training plan.',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'id', 'sender_id', 'receiver_id', 'message', 'is_read', 'isMe',
                ],
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $this->member->id,
            'receiver_id' => $this->trainer->id,
            'message' => 'Hello, I would like to discuss my training plan.',
        ]);
    }

    public function test_send_message_creates_notification(): void
    {
        $this->authenticateAs($this->member);

        TrainerBooking::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
        ]);

        $this->postJson('/api/chat/messages', [
            'receiver_id' => $this->trainer->id,
            'message' => 'Test notification.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->trainer->id,
            'notification_type' => 'chat_message',
        ]);
    }

    public function test_member_can_send_file_only_chat_message(): void
    {
        Storage::fake('public');
        $token = $this->authenticateAs($this->member);

        TrainerBooking::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
        ]);

        $response = $this->postJson('/api/chat/messages', [
            'receiver_id' => $this->trainer->id,
            'file' => UploadedFile::fake()->create('plan.pdf', 128, 'application/pdf'),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.message', '')
            ->assertJsonPath('data.file_name', 'plan.pdf')
            ->assertJsonPath('data.file_url', '/api/chat/messages/1/attachment');

        $message = ChatMessage::query()->firstOrFail();
        Storage::disk('public')->assertExists($message->file_path);

        $this->get($response->json('data.file_url').'?token='.$token)
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_send_message_without_confirmed_booking_is_blocked(): void
    {
        $this->authenticateAs($this->member);

        $response = $this->postJson('/api/chat/messages', [
            'receiver_id' => $this->trainer->id,
            'message' => 'Hello, I want to chat.',
        ]);

        $response->assertStatus(403);
    }

    public function test_contacts_includes_confirmed_booking_partners(): void
    {
        $this->authenticateAs($this->member);

        TrainerBooking::factory()->confirmed()->create([
            'member_id' => $this->member->id,
            'trainer_id' => $this->trainer->id,
        ]);

        $response = $this->getJson('/api/chat/contacts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'message', 'data',
            ]);

        $contactIds = collect($response->json('data'))->pluck('id');
        $this->assertTrue($contactIds->contains($this->trainer->id));
    }

    public function test_contacts_includes_message_exchange_partners(): void
    {
        $this->authenticateAs($this->member);

        ChatMessage::factory()->create([
            'sender_id' => $this->member->id,
            'receiver_id' => $this->trainer->id,
        ]);

        $response = $this->getJson('/api/chat/contacts');

        $response->assertStatus(200);
        $contactIds = collect($response->json('data'))->pluck('id');
        $this->assertTrue($contactIds->contains($this->trainer->id));
    }

    public function test_messages_cursor_pagination(): void
    {
        $this->authenticateAs($this->member);

        ChatMessage::factory()->count(15)->create([
            'sender_id' => $this->member->id,
            'receiver_id' => $this->trainer->id,
        ]);

        $response = $this->json('GET', '/api/chat/messages', [
            'contact_id' => $this->trainer->id,
            'limit' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'message', 'data' => [
                    'data', 'has_more', 'oldest_id',
                ],
            ]);

        $this->assertTrue($response->json('data.has_more'));
        $this->assertCount(5, $response->json('data.data'));
    }
}
