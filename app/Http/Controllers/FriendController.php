<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FriendController extends Controller
{
    // Добавить в друзья (POST /users/{username}/friend).
    // Обратная связь создаётся автоматически событием FriendshipCreated.
    public function store(Request $request, User $user)
    {
        Gate::authorize('befriend', $user);
        $request->user()->friends()->syncWithoutDetaching([$user->id]);

        return back()->with('success', $user->name . ' теперь в друзьях (дружба обоюдная)');
    }

    // Удалить из друзей (DELETE /users/{username}/friend) — связь удаляется в обе стороны
    public function destroy(Request $request, User $user)
    {
        $request->user()->friends()->detach($user->id);

        return back()->with('success', $user->name . ' удалён из друзей');
    }

    // Лента: новые картины друзей по дате создания (GET /feed)
    public function feed(Request $request)
    {
        $user = $request->user();
        $paintings = $user->feed()->paginate(12);
        $friends = $user->friends()->orderBy('name')->get();

        return view('users.feed', compact('paintings', 'friends'));
    }
}
