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
@endsection