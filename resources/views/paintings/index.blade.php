@extends('layouts.gallery')

@section('title', 'Галерея')

@section('content')
<h2 class="page-main-title mb-4">Шедевры мировой живописи</h2>

@if ($paintings->isEmpty())
    <p class="text-muted">В галерее пока нет картин.</p>
@endif

<div class="row gallery-grid row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxxl-5 g-4 mb-5">
    @foreach ($paintings as $painting)
        @include('paintings._card')
    @endforeach
</div>
@endsection
