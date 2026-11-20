<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Painting extends Model
{
    use SoftDeletes;

    // Допустимые техники живописи (выпадающий список в форме)
    public const TECHNIQUES = [
        'Масло, холст',
        'Масло, тополевая доска',
        'Масло, темпера и пастель на картоне',
        'Темпера',
        'Акварель',
        'Гуашь',
        'Акрил',
        'Пастель',
        'Фреска',
        'Графика (карандаш, уголь, тушь)',
    ];

    // Проверка на дубликат: картина с таким названием уже есть (без учёта регистра и пробелов)
    public static function isDuplicate(string $title, ?int $ignoreId = null): bool
    {
        $normalize = fn (string $s) => mb_strtolower(preg_replace('/\s+/u', ' ', trim($s)));

        return static::query()
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->get(['id', 'title'])
            ->contains(fn ($p) => $normalize($p->title) === $normalize($title));
    }

    protected $fillable = [
        'title', 'author', 'year', 'technique',
        'size', 'location', 'description', 'image',
    ];
}