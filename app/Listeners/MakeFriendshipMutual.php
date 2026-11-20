<?php

namespace App\Listeners;

use App\Events\FriendshipCreated;
use App\Models\User;

// «Обоюдная дружба»: как только A добавляет B в друзья, автоматически создаётся связь B → A
class MakeFriendshipMutual
{
    public function handle(FriendshipCreated $event): void
    {
        $friend = User::find($event->friendship->friend_id);

        // Проверка обратной связи останавливает рекурсию (attach снова вызовет это событие)
        if ($friend && ! $friend->friends()->whereKey($event->friendship->user_id)->exists()) {
            $friend->friends()->attach($event->friendship->user_id);
        }
    }
}
