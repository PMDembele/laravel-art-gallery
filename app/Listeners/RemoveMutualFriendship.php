<?php

namespace App\Listeners;

use App\Events\FriendshipDeleted;
use App\Models\User;

// Удаление дружбы тоже обоюдное: убираем обратную связь B → A
class RemoveMutualFriendship
{
    public function handle(FriendshipDeleted $event): void
    {
        $friend = User::find($event->friendship->friend_id);

        if ($friend && $friend->friends()->whereKey($event->friendship->user_id)->exists()) {
            $friend->friends()->detach($event->friendship->user_id);
        }
    }
}
