<?php

namespace App\Events;

use App\Models\Friendship;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Событие: пользователь user_id добавил friend_id в друзья
class FriendshipCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Friendship $friendship)
    {
    }
}
