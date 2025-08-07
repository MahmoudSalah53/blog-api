<?php

namespace App\Events;

use App\Models\Like;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostLiked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $like;

    public function __construct(Like $like)
    {
        $this->like = $like;
    }

    public function broadcastOn()
    {
        return new Channel('post.' . $this->like->post_id);
    }

    public function broadcastAs()
    {
        return 'post.liked';
    }
}