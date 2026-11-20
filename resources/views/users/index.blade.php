@extends('layouts.gallery')

@section('title', 'Пользователи')

@section('content')
<h2 class="page-main-title mb-4">Пользователи</h2>

<div class="list-group shadow-sm">
    @foreach ($users as $listedUser)
        <a href="{{ route('users.show', $listedUser) }}"
           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            <span>
                <strong>{{ $listedUser->name }}</strong>
                <span class="text-muted">{{ '@' . $listedUser->username }}</span>
                @if ($listedUser->isAdmin())
                    <span class="badge text-bg-warning ms-1">админ</span>
                @endif
                @if ($listedUser->is(auth()->user()))
                    <span class="badge text-bg-info ms-1">это вы</span>
                @endif
            </span>
            <span class="badge text-bg-primary rounded-pill">{{ $listedUser->paintings_count }}</span>
        </a>
    @endforeach
</div>
@endsection
