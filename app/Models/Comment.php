<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Комментарий к картине (вспомогательная сущность ЛР5)
class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['body'];

    // Картина, к которой оставлен комментарий (N — 1)
    public function painting(): BelongsTo
    {
        return $this->belongsTo(Painting::class);
    }

    // Пользователь, оставивший комментарий (N — 1)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
