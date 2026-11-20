<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Painting;
use App\Models\User;
use Illuminate\Database\Seeder;

// ЛР5: тестовая дружба и комментарии
class SocialSeeder extends Seeder
{
    public function run(): void
    {
        $anna = User::where('username', 'anna')->first();
        $petr = User::where('username', 'petr')->first();
        $admin = User::where('username', 'admin')->first();
        if (! $anna || ! $petr || ! $admin) {
            return;
        }

        // Анна добавляет Петра в друзья — обратная связь создаётся событием автоматически
        $anna->friends()->syncWithoutDetaching([$petr->id]);

        $comments = [
            ['Мона Лиза (Джоконда)', $petr, 'Улыбка действительно загадочная — сфумато творит чудеса.'],
            ['Мона Лиза (Джоконда)', $admin, 'Добавил бы, что картина была украдена из Лувра в 1911 году.'],
            ['Звёздная ночь', $anna, 'Моя любимая картина Ван Гога! Вихри неба завораживают.'],
            ['Крик', $petr, 'Одна из самых узнаваемых картин XX века.'],
            ['Постоянство памяти', $admin, 'Мягкие часы — отличный пример сюрреализма.'],
        ];

        foreach ($comments as [$title, $author, $body]) {
            $painting = Painting::where('title', $title)->first();
            if ($painting && ! $painting->comments()->where('body', $body)->exists()) {
                $comment = new Comment(['body' => $body]);
                $comment->user()->associate($author);
                $painting->comments()->save($comment);
            }
        }
    }
}
