<?php

namespace App\Http\Controllers;

use App\Events\NewChatMessage;
use App\Events\NewNotification;
use App\Models\ChatMessage;
use App\Models\Notification;
use App\Models\TrainerBooking;
use App\Models\User;
use App\Services\Chat\FileUploadService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * List chat contacts: users the authenticated user has exchanged messages with,
     * plus active booking partners (so chat is available right after booking).
     */
    public function contacts(Request $request)
    {
        $uid = $request->user()->id;

        // Users from existing messages
        $messagePartnerIds = ChatMessage::where('sender_id', $uid)->pluck('receiver_id')
            ->merge(ChatMessage::where('receiver_id', $uid)->pluck('sender_id'))
            ->unique()
            ->filter(fn($id) => $id !== $uid);

        // Users from active bookings (confirmed only)
        $bookingPartnerIds = TrainerBooking::query()
            ->where('status', TrainerBooking::STATUS_CONFIRMED)
            ->where('end_date', '>', now()) // only active bookings
            ->where(function ($q) use ($uid) {
                $q->where('member_id', $uid)
                  ->orWhere('trainer_id', $uid);
            })
            ->get()
            ->map(fn(TrainerBooking $b) => $b->member_id === $uid ? $b->trainer_id : $b->member_id)
            ->filter(fn($id) => $id !== $uid);

        $contactIds = $messagePartnerIds->merge($bookingPartnerIds)->unique()->values();

        $contacts = User::whereIn('id', $contactIds)
            ->with('role:id,name')
            ->get()
            ->map(fn(User $u) => [
                'id'   => $u->id,
                'name' => $u->full_name,
                'img'  => $u->profile_picture_url,
                'role' => $u->role?->name ?? 'member',
            ]);

        // Add unread counts per contact
        $unreadCounts = ChatMessage::where('receiver_id', $uid)
            ->where('is_read', false)
            ->groupBy('sender_id')
            ->selectRaw('sender_id, count(*) as count')
            ->pluck('count', 'sender_id');

        $contacts = $contacts->map(function ($contact) use ($unreadCounts) {
            $contact['unread_count'] = $unreadCounts[$contact['id']] ?? 0;
            return $contact;
        });

        return ApiResponse::success('Contacts loaded.', $contacts);
    }

    /**
     * List chat messages between authenticated user and a given contact.
     * Supports cursor-based pagination with `before` (message ID) and `limit`.
     */
    public function messages(Request $request)
    {
        $request->validate([
            'contact_id' => 'required|integer|exists:users,id',
            'before'     => 'nullable|integer|exists:chat_messages,id',
            'limit'      => 'nullable|integer|min:1|max:200',
        ]);

        $uid = $request->user()->id;
        $contactId = $request->integer('contact_id');
        $limit = $request->integer('limit', 100);

        $query = ChatMessage::where(function ($q) use ($uid, $contactId) {
            $q->where('sender_id', $uid)->where('receiver_id', $contactId);
        })->orWhere(function ($q) use ($uid, $contactId) {
            $q->where('sender_id', $contactId)->where('receiver_id', $uid);
        });

        // Cursor pagination: load messages older than `before`
        if ($before = $request->integer('before')) {
            $query->where('id', '<', $before);
        }

        $messages = $query
            ->with(['sender:id,full_name'])
            ->orderByDesc('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $messages->count() > $limit;
        $messages = $messages->take($limit)->reverse()->values();

        $result = [
            'data' => $messages->map(fn(ChatMessage $m) => $this->formatMessage($m, $uid)),
            'has_more' => $hasMore,
            'oldest_id' => $messages->first()?->id,
        ];

        // Mark incoming messages as read
        ChatMessage::where('sender_id', $contactId)
            ->where('receiver_id', $uid)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return ApiResponse::success('Messages loaded.', $result);
    }

    /**
     * Send a chat message.
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'message'     => 'nullable|required_without:file|string|max:2000',
            'file'        => 'nullable|file|max:10240',
        ]);

        $uid = $request->user()->id;
        $receiverId = $data['receiver_id'];
        $fileUploadService = app(FileUploadService::class);
        $file = $request->file('file');

        // Check if there is an existing chat history
        $hasHistory = ChatMessage::where(function ($q) use ($uid, $receiverId) {
            $q->where('sender_id', $uid)->where('receiver_id', $receiverId);
        })->orWhere(function ($q) use ($uid, $receiverId) {
            $q->where('sender_id', $receiverId)->where('receiver_id', $uid);
        })->exists();

        if (!$hasHistory) {
            // Check if there is a confirmed booking
            $hasConfirmedBooking = TrainerBooking::query()
                ->where('status', TrainerBooking::STATUS_CONFIRMED)
                ->where(function ($q) use ($uid, $receiverId) {
                    $q->where(function ($inner) use ($uid, $receiverId) {
                        $inner->where('member_id', $uid)->where('trainer_id', $receiverId);
                    })->orWhere(function ($inner) use ($uid, $receiverId) {
                        $inner->where('member_id', $receiverId)->where('trainer_id', $uid);
                    });
                })
                ->exists();

            if (!$hasConfirmedBooking) {
                return ApiResponse::error('Anda hanya dapat mengirim pesan setelah booking dikonfirmasi.', [], 403);
            }
        }

        $attachment = $file ? $fileUploadService->handle($file) : [];

        $msg = ChatMessage::create([
            'sender_id'   => $uid,
            'receiver_id' => $receiverId,
            'message'     => $data['message'] ?? '',
        ] + $attachment);

        $senderName = $request->user()->full_name ?? 'User';
        $notificationBody = $data['message'] ?? ('Attachment: ' . ($attachment['file_name'] ?? 'file'));

        // Also dispatch a notification to the receiver so they get a push/toast
        $notif = Notification::create([
            'user_id'           => $data['receiver_id'],
            'title'             => 'Pesan baru dari ' . $senderName,
            'body'              => mb_substr($notificationBody, 0, 120),
            'notification_type' => 'chat_message',
            'is_read'           => false,
        ]);

        try {
            broadcast(new NewChatMessage($msg));
        } catch (\Throwable $e) {
            logger()->warning('Broadcast failed: ' . $e->getMessage());
        }

        try {
            broadcast(new NewNotification($notif));
        } catch (\Throwable $e) {
            logger()->warning('Broadcast NewNotification for chat failed: ' . $e->getMessage());
        }

        return ApiResponse::success('Message sent.', $this->formatMessage($msg, $uid), 201);
    }

    public function attachment(Request $request, ChatMessage $message)
    {
        $uid = $request->user()->id;

        abort_unless($message->sender_id === $uid || $message->receiver_id === $uid, 403);
        abort_unless($message->file_path && Storage::disk('public')->exists($message->file_path), 404);

        return Storage::disk('public')->response($message->file_path, $message->file_name);
    }

    private function formatMessage(ChatMessage $message, int $currentUserId): array
    {
        return [
            'id'          => $message->id,
            'sender_id'   => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'message'     => $message->message ?? '',
            'created_at'  => $message->created_at?->toISOString(),
            'sender_name' => $message->sender?->full_name ?? '',
            'is_read'     => $message->is_read,
            'isMe'        => $message->sender_id === $currentUserId,
            'file_url'    => $message->file_path ? "/api/chat/messages/{$message->id}/attachment" : null,
            'file_name'   => $message->file_name,
            'file_size'   => $message->file_size,
        ];
    }
}
