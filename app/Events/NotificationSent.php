<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;

    public string $title;

    public string $message;

    public ?array $data;

    public function __construct(User $user, string $title, string $message, ?array $data = null)
    {
        $this->userId = $user->id;
        $this->title = $title;
        $this->message = $message;
        $this->data = $data;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('user.'.$this->userId);
    }

    public function broadcastWith(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'user_id' => $this->userId,
            'created_at' => now()->toISOString(),
        ];
    }
}
