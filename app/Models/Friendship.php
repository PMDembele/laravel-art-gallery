<?php

namespace App\Models;

use App\Events\FriendshipCreated;
use App\Events\FriendshipDeleted;
use Illuminate\Database\Eloquent\Relations\Pivot;

// Промежуточная модель связи «пользователь — друг» (таблица friendships).
// Собственная модель pivot нужна, чтобы attach()/detach() вызывали события Eloquent.
class Friendship extends Pivot
{
    protected $table = 'friendships';

    public $incrementing = true;

    protected $dispatchesEvents = [
        'created' => FriendshipCreated::class,
        'deleted' => FriendshipDeleted::class,
    ];
}
