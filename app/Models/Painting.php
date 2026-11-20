<?php

namespace App\Models;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class Painting extends Model
{
    use SoftDeletes;

    // user_id не в $fillable: владелец берётся только из текущего пользователя, а не из формы
    protected $fillable = [
        'title', 'author', 'year', 'technique',
        'size', 'location', 'description', 'image',
    ];

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

    // Пользователь, добавивший картину (N — 1)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Проверка прав на уровне модели (Events + Closures).
    // Даже если контроллер забудет проверку, модель не даст изменить чужую картину.
    protected static function booted(): void
    {
        static::creating(function (Painting $painting) {
            if ($painting->user_id === null && Auth::check()) {
                $painting->user_id = Auth::id(); // записываем ID пользователя, добавившего объект
            }
            static::ensureAllowed('create-painting');
        });

        static::updating(fn (Painting $painting) => static::ensureAllowed('update-painting', $painting));

        static::deleting(function (Painting $painting) {
            static::ensureAllowed(
                $painting->isForceDeleting() ? 'force-delete-painting' : 'delete-painting',
                $painting
            );
        });

        static::restoring(fn (Painting $painting) => static::ensureAllowed('restore-painting', $painting));
    }

    // Консольные команды (миграции, сидеры, tinker) работают без пользователя — им разрешено всё.
    // В веб-запросе действие должно быть разрешено соответствующим Gate.
    private static function ensureAllowed(string $ability, ?Painting $painting = null): void
    {
        if (app()->runningInConsole() && ! Auth::check()) {
            return;
        }

        $arguments = $painting ? [$painting] : [];
        if (Gate::denies($ability, $arguments)) {
            throw new AuthorizationException('Недостаточно прав для этого действия с картиной.');
        }
    }

    // Проверка на дубликат: картина с таким названием уже есть (без учёта регистра и пробелов)
    public static function isDuplicate(string $title, ?int $ignoreId = null): bool
    {
        $normalize = fn (string $s) => mb_strtolower(preg_replace('/\s+/u', ' ', trim($s)));

        return static::query()
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->get(['id', 'title'])
            ->contains(fn ($p) => $normalize($p->title) === $normalize($title));
    }
}
