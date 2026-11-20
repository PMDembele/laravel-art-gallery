<?php

namespace App\Events;

use App\Models\Friendship;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Событие: пользователь user_id удалил friend_id из друзей
class FriendshipDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Friendship $friendship)
    {
    }
}
