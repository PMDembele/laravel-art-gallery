<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Painting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    // Добавление комментария к своей или чужой картине (POST /paintings/{painting}/comments)
    public function store(Request $request, Painting $painting)
    {
        Gate::authorize('create-comment');

        $data = $request->validate(
            ['body' => 'required|string|min:2|max:1000'],
            [
                'body.required' => 'Напишите текст комментария.',
                'body.min'      => 'Комментарий слишком короткий.',
                'body.max'      => 'Комментарий не должен превышать :max символов.',
            ]
        );

        $comment = new Comment($data);
        $comment->user()->associate($request->user());   // кто оставил комментарий
        $painting->comments()->save($comment);            // к какой картине

        return redirect()->to(route('paintings.show', $painting) . '#comment-' . $comment->id)
            ->with('success', 'Комментарий добавлен');
    }

    // Удаление комментария (DELETE /comments/{comment}) — автор комментария или администратор
    public function destroy(Comment $comment)
    {
        Gate::authorize('delete-comment', $comment);
        $painting = $comment->painting;
        $comment->delete();

        return redirect()->to(route('paintings.show', $painting) . '#comments')
            ->with('success', 'Комментарий удалён');
    }
}
