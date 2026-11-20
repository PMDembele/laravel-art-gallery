<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'АртГалерея')</title>
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
</head>
<body>
<div class="container py-4">
    <nav class="navbar navbar-expand-md bg-white shadow-sm rounded mb-4 px-3">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('paintings.index') }}">
            <span class="logo">🎨</span>
            <span class="site-name">АртГалерея</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Меню">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link @if (request()->routeIs('paintings.index')) active @endif"
                       href="{{ route('paintings.index') }}">Все картины</a>
                </li>
                @auth
                    <li class="nav-item">
                        <a class="nav-link @if (request()->routeIs('users.index')) active @endif"
                           href="{{ route('users.index') }}">Пользователи</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if (request()->is('users/' . auth()->user()->username)) active @endif"
                           href="{{ route('users.show', auth()->user()) }}">Мои картины</a>
                    </li>
                @endauth
            </ul>

            <div class="d-flex flex-wrap align-items-center gap-2">
                @auth
                    @can('create-painting')
                        <a class="btn btn-primary" href="{{ route('paintings.create') }}">Добавить</a>
                    @endcan
                    <a class="btn btn-outline-secondary" href="{{ route('profile.edit') }}">
                        {{ auth()->user()->name }}
                        @if (auth()->user()->isAdmin())
                            <span class="badge text-bg-warning">админ</span>
                        @endif
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">Выйти</button>
                    </form>
                @else
                    <a class="btn btn-outline-primary" href="{{ route('login') }}">Войти</a>
                    <a class="btn btn-primary" href="{{ route('register') }}">Регистрация</a>
                @endauth
            </div>
        </div>
    </nav>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @yield('content')
</div>
<script src="{{ mix('js/gallery.js') }}"></script>
</body>
</html>
