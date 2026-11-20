{{-- Кнопка «Добавить в друзья / Удалить из друзей». Для самого себя не показывается --}}
@can('befriend', $target)
    @if (in_array((int) $target->id, array_map('intval', auth()->user()->friendIds()), true))
        <form action="{{ route('friends.destroy', $target) }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">Удалить из друзей</button>
        </form>
    @else
        <form action="{{ route('friends.store', $target) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-success">+ В друзья</button>
        </form>
    @endif
@endcan
