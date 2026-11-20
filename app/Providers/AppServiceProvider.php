<?php

namespace App\Providers;

use App\Models\Painting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Правила доступа к картинам (Gates).
        // Используются в маршрутах (middleware can:), контроллере (Gate::authorize),
        // шаблонах (@can) и модели (события Painting).

        // 1) Создавать картины может любой авторизованный пользователь
        Gate::define('create-painting', fn (User $user) => true);

        // 2) Редактировать — только автор записи или администратор
        Gate::define('update-painting', fn (User $user, Painting $painting) =>
            $user->isAdmin() || $user->id === $painting->user_id);

        // 3) Мягко удалить — автор записи или администратор
        Gate::define('delete-painting', fn (User $user, Painting $painting) =>
            $user->isAdmin() || $user->id === $painting->user_id);

        // 4) Восстановить и удалить без возврата — только администратор
        Gate::define('restore-painting', fn (User $user, Painting $painting) => $user->isAdmin());
        Gate::define('force-delete-painting', fn (User $user, Painting $painting) => $user->isAdmin());

        // Видеть удалённые картины: администратор — у всех, пользователь — свои
        Gate::define('view-trashed', fn (User $user, User $owner) =>
            $user->isAdmin() || $user->id === $owner->id);
    }
}
