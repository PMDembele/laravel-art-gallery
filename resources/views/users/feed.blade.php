@extends('layouts.gallery')

@section('title', 'Лента друзей')

@section('content')
<div class="row g-4">
    <aside class="col-lg-3">
        <div class="list-group shadow-sm">
            <div class="list-group-item fw-semibold">Мои друзья ({{ $friends->count() }})</div>
            @forelse ($friends as $friend)
                <a href="{{ route('users.show', $friend) }}" class="list-group-item list-group-item-action">
                    {{ $friend->name }}
                    <small class="d-block text-muted">{{ '@' . $friend->username }}</small>
                </a>
            @empty
                <div class="list-group-item text-muted small">
                    Друзей пока нет. Найдите их в списке <a href="{{ route('users.index') }}">пользователей</a>.
                </div>
            @endforelse
        </div>
    </aside>

    <section class="col-lg-9">
        <h2 class="page-main-title mb-1">Лента друзей</h2>
        <p class="text-muted mb-4">Новые картины ваших друзей — сначала самые свежие.</p>

        @if ($paintings->isEmpty())
            <p class="text-muted">В ленте пока пусто.</p>
        @endif

        <div class="row gallery-grid row-cols-1 row-cols-sm-2 row-cols-xl-3 g-4 mb-4">
            @foreach ($paintings as $painting)
                <div class="col">
                    <div class="small text-muted mb-1">
                        {{ $painting->created_at->format('d.m.Y H:i') }} · {{ $painting->user->name }}
                    </div>
                    <div class="row row-cols-1">
                        @include('paintings._card', ['showOwner' => false])
                    </div>
                </div>
            @endforeach
        </div>

        {{ $paintings->links('pagination::bootstrap-5') }}
    </section>
</div>
@endsection
