<?php

use App\Http\Controllers\PaintingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/paintings');
// Проверка дубликата «на лету» (AJAX) — объявлена до resource, чтобы не совпасть с /paintings/{painting}
Route::get('paintings/check-duplicate', [PaintingController::class, 'checkDuplicate'])
    ->name('paintings.check-duplicate');
Route::resource('paintings', PaintingController::class);