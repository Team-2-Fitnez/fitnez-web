<?php

namespace App\Events;

use App\Models\Attendance;
use App\Support\SocketioBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceChecked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Attendance $attendance;
    public string $action;

    public function __construct(Attendance $attendance, string $action)
    {
        $this->attendance = $attendance;
        $this->action = $action;
    }

    public function broadcast(): void
    {
        $payload = [
            'id'              => $this->attendance->id,
            'user_id'         => $this->attendance->user_id,
            'user_name'       => $this->attendance->user?->full_name ?? '',
            'user_email'      => $this->attendance->user?->email ?? '',
            'attendance_type' => $this->attendance->attendance_type,
            'check_in_time'   => $this->attendance->check_in_time?->toISOString(),
            'check_out_time'  => $this->attendance->check_out_time?->toISOString(),
            'action'          => $this->action,
        ];

        SocketioBroadcast::send('attendance.global', 'attendance-update', $payload);
    }
}
