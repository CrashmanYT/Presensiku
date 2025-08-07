<?php

namespace App\Events;

use App\Models\StudentAttendance;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentAttendanceUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public StudentAttendance $studentAttendance;

    /**
     * Create a new event instance.
     */
    public function __construct(StudentAttendance $studentAttendance)
    {
        $this->studentAttendance = $studentAttendance;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
