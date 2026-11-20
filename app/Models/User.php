<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // is_admin намеренно не в $fillable: его нельзя выставить через форму регистрации
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    // Картины, добавленные пользователем (1 — N)
    public function paintings(): HasMany
    {
        return $this->hasMany(Painting::class);
    }

    // Комментарии, оставленные пользователем (1 — N)
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // Друзья пользователя (M — N через таблицу friendships).
    // Модель Friendship вызывает события, по которым дружба становится обоюдной.
    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->using(Friendship::class)
            ->withTimestamps();
    }

    // Пользователи, добавившие этого пользователя в друзья (обратная сторона связи)
    public function friendOf(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
            ->using(Friendship::class)
            ->withTimestamps();
    }

    public function isFriendWith(User $user): bool
    {
        return $this->friends()->whereKey($user->id)->exists();
    }

    // ID друзей (кэшируется на время запроса — используется для подсветки комментариев)
    public function friendIds(): array
    {
        return $this->friendIdsCache ??= $this->friends()->pluck('users.id')->all();
    }

    private ?array $friendIdsCache = null;

    // Лента: новые картины друзей в хронологическом порядке (сначала новые)
    public function feed()
    {
        return Painting::whereIn('user_id', $this->friends()->select('users.id'))
            ->with('user')
            ->withCount('comments')
            ->latest();
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}
