<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    // Список всех пользователей (GET /users)
    public function index()
    {
        $users = User::withCount('paintings')->orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    // Картины одного пользователя (GET /users/{username}).
    // Автор и администратор видят также мягко удалённые картины.
    public function show(Request $request, User $user)
    {
        $canSeeTrashed = Gate::allows('view-trashed', $user);

        $paintings = $user->paintings()
            ->when($canSeeTrashed, fn ($q) => $q->withTrashed())
            ->withCount('comments')
            ->latest()
            ->get();

        $user->load(['friends' => fn ($q) => $q->orderBy('name')]);

        // Навигация «предыдущий / следующий пользователь» (в том же порядке, что и список)
        $users = User::orderBy('name')->get(['id', 'name', 'username']);
        $position = $users->search(fn ($u) => $u->id === $user->id);
        $previous = $position > 0 ? $users[$position - 1] : null;
        $next = $position < $users->count() - 1 ? $users[$position + 1] : null;

        return view('users.show', compact('user', 'paintings', 'canSeeTrashed', 'users', 'previous', 'next'));
    }
}
