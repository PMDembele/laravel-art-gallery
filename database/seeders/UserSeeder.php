<?php

namespace Database\Seeders;

use App\Models\Painting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    // Тестовые пользователи (пароль у всех: password).
    // Флаг is_admin пользователю admin выставляется отдельно через tinker (см. README).
    public const USERS = [
        ['name' => 'Администратор', 'username' => 'admin', 'email' => 'admin@example.com'],
        ['name' => 'Анна Иванова', 'username' => 'anna', 'email' => 'anna@example.com'],
        ['name' => 'Пётр Смирнов', 'username' => 'petr', 'email' => 'petr@example.com'],
    ];

    public function run(): void
    {
        $users = collect(self::USERS)->map(function (array $data) {
            $user = User::firstOrNew(['email' => $data['email']]);
            $user->fill([
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => Hash::make('password'),
            ]);
            $user->email_verified_at ??= now();
            $user->save();

            return $user;
        });

        // Картинам без владельца (добавленным до ЛР4) назначаем пользователей по очереди
        $owners = $users->where('username', '!=', 'admin')->values();
        Painting::withTrashed()->whereNull('user_id')->orderBy('id')->get()
            ->each(function (Painting $painting, int $index) use ($owners) {
                $painting->user_id = $owners[$index % $owners->count()]->id;
                $painting->saveQuietly();
            });
    }
}
