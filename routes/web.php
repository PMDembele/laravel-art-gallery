<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\PaintingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/paintings');

// После входа Breeze перенаправляет на dashboard — ведём пользователя в его галерею
Route::get('/dashboard', fn () => redirect()->route('users.show', auth()->user()))
    ->middleware('auth')->name('dashboard');

// Проверка дубликата «на лету» (AJAX) — объявлена до resource, чтобы не совпасть с /paintings/{painting}
Route::get('paintings/check-duplicate', [PaintingController::class, 'checkDuplicate'])
    ->name('paintings.check-duplicate');

Route::middleware('auth')->group(function () {
    // Пользователи и их картины; ссылка строится по username: /users/{username}
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user:username}', [UserController::class, 'show'])->name('users.show');

    // Создание, редактирование и удаление — только для авторизованных; права проверяют Gates
    Route::get('/paintings/create', [PaintingController::class, 'create'])
        ->middleware('can:create-painting')->name('paintings.create');
    Route::post('/paintings', [PaintingController::class, 'store'])
        ->middleware('can:create-painting')->name('paintings.store');
    Route::get('/paintings/{painting}/edit', [PaintingController::class, 'edit'])
        ->middleware('can:update-painting,painting')->name('paintings.edit');
    Route::match(['put', 'patch'], '/paintings/{painting}', [PaintingController::class, 'update'])
        ->middleware('can:update-painting,painting')->name('paintings.update');
    Route::delete('/paintings/{painting}', [PaintingController::class, 'destroy'])
        ->middleware('can:delete-painting,painting')->name('paintings.destroy');

    // Только администратор: восстановление и удаление без возврата (с учётом удалённых записей)
    Route::patch('/paintings/{painting}/restore', [PaintingController::class, 'restore'])
        ->withTrashed()->middleware('can:restore-painting,painting')->name('paintings.restore');
    Route::delete('/paintings/{painting}/force', [PaintingController::class, 'forceDelete'])
        ->withTrashed()->middleware('can:force-delete-painting,painting')->name('paintings.force-delete');

    // ЛР5. Комментарии к картинам (вспомогательная сущность)
    Route::post('/paintings/{painting}/comments', [CommentController::class, 'store'])
        ->middleware('can:create-comment')->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->middleware('can:delete-comment,comment')->name('comments.destroy');

    // ЛР5. Дружба (M — N) и лента друзей
    Route::post('/users/{user:username}/friend', [FriendController::class, 'store'])
        ->middleware('can:befriend,user')->name('friends.store');
    Route::delete('/users/{user:username}/friend', [FriendController::class, 'destroy'])->name('friends.destroy');
    Route::get('/feed', [FriendController::class, 'feed'])->name('feed');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Просмотр галереи доступен всем
Route::get('/paintings', [PaintingController::class, 'index'])->name('paintings.index');
Route::get('/paintings/{painting}', [PaintingController::class, 'show'])->name('paintings.show');

require __DIR__.'/auth.php';
