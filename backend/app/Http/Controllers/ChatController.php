<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\TrainerBooking;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

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

        // Users from active bookings (pending/confirmed)
        $bookingPartnerIds = TrainerBooking::active()
            ->where(function ($q) use ($uid) {
                $q->where('member_id', $uid)->orWhere('trainer_id', $uid);
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

        return ApiResponse::success('Contacts loaded.', $contacts);
    }

    /**
     * List chat messages between authenticated user and a given contact.
     */
    public function messages(Request $request)
    {
        $request->validate(['contact_id' => 'required|integer|exists:users,id']);

        $uid = $request->user()->id;
        $contactId = $request->integer('contact_id');

        $messages = ChatMessage::where(function ($q) use ($uid, $contactId) {
            $q->where('sender_id', $uid)->where('receiver_id', $contactId);
        })->orWhere(function ($q) use ($uid, $contactId) {
            $q->where('sender_id', $contactId)->where('receiver_id', $uid);
        })
        ->with(['sender:id,full_name'])
        ->orderBy('created_at')
        ->get()
        ->map(fn(ChatMessage $m) => [
            'id'          => $m->id,
            'sender_id'   => $m->sender_id,
            'receiver_id' => $m->receiver_id,
            'message'     => $m->message,
            'created_at'  => $m->created_at?->toISOString(),
            'sender_name' => $m->sender?->full_name ?? '',
            'is_read'     => $m->is_read,
            'isMe'        => $m->sender_id === $uid,
        ]);

        // Mark incoming messages as read
        ChatMessage::where('sender_id', $contactId)
            ->where('receiver_id', $uid)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return ApiResponse::success('Messages loaded.', $messages);
    }

    /**
     * Send a chat message.
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'message'     => 'required|string|max:2000',
        ]);

        $msg = ChatMessage::create([
            'sender_id'   => $request->user()->id,
            'receiver_id' => $data['receiver_id'],
            'message'     => $data['message'],
        ]);

        return ApiResponse::success('Message sent.', [
            'id'          => $msg->id,
            'sender_id'   => $msg->sender_id,
            'receiver_id' => $msg->receiver_id,
            'message'     => $msg->message,
            'created_at'  => $msg->created_at?->toISOString(),
            'is_read'     => false,
            'isMe'        => true,
        ], 201);
    }
}
