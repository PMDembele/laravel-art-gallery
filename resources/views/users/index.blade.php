@extends('layouts.gallery')

@section('title', 'Пользователи')

@section('content')
<h2 class="page-main-title mb-4">Пользователи</h2>

<div class="list-group shadow-sm">
    @foreach ($users as $listedUser)
        @php $isFriend = in_array((int) $listedUser->id, array_map('intval', auth()->user()->friendIds()), true); @endphp
        <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
            <a href="{{ route('users.show', $listedUser) }}" class="text-decoration-none">
                <strong>{{ $listedUser->name }}</strong>
                <span class="text-muted">{{ '@' . $listedUser->username }}</span>
                @if ($listedUser->isAdmin())
                    <span class="badge text-bg-warning ms-1">админ</span>
                @endif
                @if ($listedUser->is(auth()->user()))
                    <span class="badge text-bg-info ms-1">это вы</span>
                @elseif ($isFriend)
                    <span class="badge text-bg-success ms-1">★ друг</span>
                @endif
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-primary rounded-pill" title="Картин">{{ $listedUser->paintings_count }} картин</span>
                @include('users._friend_button', ['target' => $listedUser])
            </div>
        </div>
    @endforeach
</div>
@endsection
