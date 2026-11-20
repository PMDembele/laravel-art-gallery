{{-- Карточка картины. Кнопки показываются только при наличии прав (проверка на уровне интерфейса) --}}
<div class="col">
    <article class="card h-100 painting-card @if ($painting->trashed()) border-danger opacity-75 @endif">
        @if ($painting->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($painting->image))
            <img src="{{ asset('storage/' . $painting->image) }}" class="card-img-top" alt="{{ $painting->title }}">
        @else
            {{-- Файл изображения отсутствует — показываем заглушку вместо «битой» картинки --}}
            <div class="card-img-top bg-secondary-subtle text-secondary d-flex align-items-center justify-content-center"
                 style="aspect-ratio: 3 / 2">Нет изображения</div>
        @endif
        <div class="card-body">
            @if ($painting->trashed())
                <span class="badge text-bg-danger mb-2">Удалена {{ $painting->deleted_at->format('d.m.Y') }}</span>
            @endif
            <h3 class="card-title h5">{{ $painting->title }}</h3>
            <p class="card-text">
                <strong>Автор:</strong> {{ $painting->author }}<br>
                <strong>Год:</strong> {{ $painting->year }}
                @isset($painting->comments_count)
                    <br><strong>Комментарии:</strong> {{ $painting->comments_count }}
                @endisset
                @if ($showOwner ?? true)
                    <br><strong>Добавил:</strong>
                    @if ($painting->user)
                        @auth
                            <a href="{{ route('users.show', $painting->user) }}">{{ $painting->user->name }}</a>
                        @else
                            {{ $painting->user->name }}
                        @endauth
                    @else
                        —
                    @endif
                @endif
            </p>
        </div>
        <div class="card-footer bg-transparent border-0 px-3 pb-3 pt-0">
            <div class="d-flex flex-wrap gap-1">
                @if ($painting->trashed())
                    @can('restore-painting', $painting)
                        <form action="{{ route('paintings.restore', $painting) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-success btn-sm">Восстановить</button>
                        </form>
                    @endcan
                    @can('force-delete-painting', $painting)
                        <form action="{{ route('paintings.force-delete', $painting) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Удалить картину навсегда? Это действие нельзя отменить.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Удалить навсегда</button>
                        </form>
                    @endcan
                @else
                    <a href="{{ route('paintings.show', $painting) }}" class="btn btn-outline-primary btn-sm">Подробнее</a>
                    @can('update-painting', $painting)
                        <a href="{{ route('paintings.edit', $painting) }}" class="btn btn-outline-secondary btn-sm">Изменить</a>
                    @endcan
                    @can('delete-painting', $painting)
                        <form action="{{ route('paintings.destroy', $painting) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Удалить картину?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Удалить</button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    </article>
</div>
