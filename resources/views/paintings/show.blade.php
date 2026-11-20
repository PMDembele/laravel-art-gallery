@extends('layouts.gallery')

@section('title', $painting->title)

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-5">
                <img src="{{ asset('storage/' . $painting->image) }}" class="img-fluid rounded w-100" alt="{{ $painting->title }}">
            </div>
            <div class="col-md-7">
                <h2>{{ $painting->title }}</h2>
                <dl class="row">
                    <dt class="col-sm-4">Автор</dt><dd class="col-sm-8">{{ $painting->author }}</dd>
                    <dt class="col-sm-4">Год</dt><dd class="col-sm-8">{{ $painting->year }}</dd>
                    <dt class="col-sm-4">Техника</dt><dd class="col-sm-8">{{ $painting->technique }}</dd>
                    <dt class="col-sm-4">Размер</dt><dd class="col-sm-8">{{ $painting->size }}</dd>
                    <dt class="col-sm-4">Хранится</dt><dd class="col-sm-8">{{ $painting->location }}</dd>
                    <dt class="col-sm-4">Добавил</dt>
                    <dd class="col-sm-8">
                        @if ($painting->user)
                            @auth
                                <a href="{{ route('users.show', $painting->user) }}">{{ $painting->user->name }}</a>
                            @else
                                {{ $painting->user->name }}
                            @endauth
                        @else
                            —
                        @endif
                    </dd>
                </dl>
                <p>{{ $painting->description }}</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('paintings.index') }}" class="btn btn-secondary">Ко всем картинам</a>
                    @can('update-painting', $painting)
                        <a href="{{ route('paintings.edit', $painting) }}" class="btn btn-primary">Изменить</a>
                    @endcan
                    @can('delete-painting', $painting)
                        <form action="{{ route('paintings.destroy', $painting) }}" method="POST"
                              onsubmit="return confirm('Удалить картину?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">Удалить</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ЛР5. Комментарии к картине --}}
<section class="card mt-4" id="comments">
    <div class="card-body">
        <h3 class="h4 mb-3">Комментарии <span class="badge text-bg-secondary">{{ $painting->comments->count() }}</span></h3>

        @auth
            @if (count($friendIds))
                <p class="small text-muted mb-3">
                    <span class="badge text-bg-success">★ друг</span> — комментарии ваших друзей подсвечены.
                </p>
            @endif
        @endauth

        @forelse ($painting->comments as $comment)
            @php $fromFriend = in_array((int) $comment->user_id, array_map('intval', $friendIds), true); @endphp
            <article id="comment-{{ $comment->id }}"
                     class="comment border rounded p-3 mb-2 @if ($fromFriend) comment-friend border-success bg-success-subtle @endif">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        @if ($fromFriend)
                            <span class="badge text-bg-success me-1" title="Комментарий друга">★ друг</span>
                        @endif
                        @auth
                            <a href="{{ route('users.show', $comment->user) }}" class="fw-semibold">{{ $comment->user->name }}</a>
                        @else
                            <span class="fw-semibold">{{ $comment->user->name }}</span>
                        @endauth
                        @if ((int) $comment->user_id === (int) $painting->user_id)
                            <span class="badge text-bg-light border">автор публикации</span>
                        @endif
                        <small class="text-muted ms-1">{{ $comment->created_at->format('d.m.Y H:i') }}</small>
                    </div>
                    @can('delete-comment', $comment)
                        <form action="{{ route('comments.destroy', $comment) }}" method="POST"
                              onsubmit="return confirm('Удалить комментарий?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                        </form>
                    @endcan
                </div>
                <p class="mb-0 mt-2" style="white-space: pre-line">{{ $comment->body }}</p>
            </article>
        @empty
            <p class="text-muted">Комментариев пока нет.</p>
        @endforelse

        @auth
            @can('create-comment')
                <form action="{{ route('comments.store', $painting) }}" method="POST" class="mt-3">
                    @csrf
                    <label for="body" class="form-label">Ваш комментарий</label>
                    <textarea id="body" name="body" rows="3" maxlength="1000" required
                              class="form-control @error('body') is-invalid @enderror">{{ old('body') }}</textarea>
                    @error('body')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="btn btn-primary mt-2">Отправить</button>
                </form>
            @endcan
        @else
            <p class="mt-3 mb-0">
                <a href="{{ route('login') }}">Войдите</a>, чтобы оставить комментарий.
            </p>
        @endauth
    </div>
</section>
@endsection