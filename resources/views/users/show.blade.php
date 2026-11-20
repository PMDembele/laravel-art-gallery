@extends('layouts.gallery')

@section('title', 'Картины — ' . $user->name)

@section('content')
<div class="row g-4">
    {{-- Навигация по пользователям средствами шаблона --}}
    <aside class="col-lg-3">
        <div class="list-group shadow-sm mb-3">
            <div class="list-group-item fw-semibold">Пользователи</div>
            @foreach ($users as $listedUser)
                <a href="{{ route('users.show', $listedUser) }}"
                   class="list-group-item list-group-item-action @if ($listedUser->is($user)) active @endif">
                    {{ $listedUser->name }}
                    <small class="d-block @if ($listedUser->is($user)) text-white-50 @else text-muted @endif">{{ '@' . $listedUser->username }}</small>
                </a>
            @endforeach
        </div>

        {{-- ЛР5. Друзья просматриваемого пользователя --}}
        <div class="list-group shadow-sm">
            <div class="list-group-item fw-semibold">Друзья ({{ $user->friends->count() }})</div>
            @forelse ($user->friends as $friend)
                <a href="{{ route('users.show', $friend) }}" class="list-group-item list-group-item-action small">
                    {{ $friend->name }}
                </a>
            @empty
                <div class="list-group-item text-muted small">Пока нет друзей</div>
            @endforelse
        </div>
    </aside>

    <section class="col-lg-9">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="page-main-title mb-0">
                {{ $user->name }}
                <small class="text-muted fs-5">{{ '@' . $user->username }}</small>
                @if ($user->isAdmin())
                    <span class="badge text-bg-warning fs-6 align-middle">админ</span>
                @endif
            </h2>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @include('users._friend_button', ['target' => $user])
            </div>
            <div class="btn-group">
                @if ($previous)
                    <a href="{{ route('users.show', $previous) }}" class="btn btn-outline-secondary">&larr; {{ $previous->name }}</a>
                @endif
                @if ($next)
                    <a href="{{ route('users.show', $next) }}" class="btn btn-outline-secondary">{{ $next->name }} &rarr;</a>
                @endif
            </div>
        </div>

        @if ($canSeeTrashed)
            <p class="text-muted small">Показаны все картины пользователя, включая удалённые (отмечены красным).</p>
        @endif

        @if ($paintings->isEmpty())
            <p class="text-muted">У пользователя пока нет картин.</p>
        @endif

        <div class="row gallery-grid row-cols-1 row-cols-sm-2 row-cols-xl-3 g-4 mb-5">
            @foreach ($paintings as $painting)
                @include('paintings._card', ['showOwner' => false])
            @endforeach
        </div>
    </section>
</div>
@endsection
