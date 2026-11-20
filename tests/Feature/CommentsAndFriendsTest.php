<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Painting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// ЛР5: комментарии (вспомогательная сущность), дружба M — N, лента и подсветка
class CommentsAndFriendsTest extends TestCase
{
    use RefreshDatabase;

    private User $anna;
    private User $petr;
    private User $ivan;
    private Painting $annaPainting;
    private Painting $petrPainting;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->anna = User::factory()->create(['username' => 'anna', 'name' => 'Анна']);
        $this->petr = User::factory()->create(['username' => 'petr', 'name' => 'Пётр']);
        $this->ivan = User::factory()->create(['username' => 'ivan', 'name' => 'Иван']);

        $this->annaPainting = $this->makePainting($this->anna, 'Картина Анны', now()->subDays(2));
        $this->petrPainting = $this->makePainting($this->petr, 'Картина Петра', now()->subDay());
    }

    private function makePainting(User $user, string $title, $createdAt): Painting
    {
        $painting = $user->paintings()->create([
            'title' => $title, 'author' => 'Художник', 'year' => 1900, 'technique' => 'Акварель',
            'size' => '1', 'location' => 'Музей', 'description' => 'Описание', 'image' => 'paintings/x.jpg',
        ]);
        $painting->forceFill(['created_at' => $createdAt])->saveQuietly();

        return $painting;
    }

    public function test_user_can_comment_own_and_foreign_paintings(): void
    {
        $this->actingAs($this->anna)
            ->post("/paintings/{$this->annaPainting->id}/comments", ['body' => 'Мой комментарий'])
            ->assertRedirect();
        $this->actingAs($this->anna)
            ->post("/paintings/{$this->petrPainting->id}/comments", ['body' => 'Чужая картина'])
            ->assertRedirect();

        $this->assertSame(2, $this->anna->comments()->count());
        $this->assertSame(['Чужая картина'], $this->petrPainting->comments->pluck('body')->all());
        $this->assertSame($this->anna->id, Comment::where('body', 'Чужая картина')->value('user_id'));

        $this->get("/paintings/{$this->petrPainting->id}")->assertSee('Чужая картина');
    }

    public function test_guest_cannot_comment_and_empty_comment_is_rejected(): void
    {
        $this->post("/paintings/{$this->petrPainting->id}/comments", ['body' => 'Гость'])->assertRedirect('/login');
        $this->actingAs($this->anna)
            ->post("/paintings/{$this->petrPainting->id}/comments", ['body' => ''])
            ->assertSessionHasErrors('body');
        $this->assertSame(0, Comment::count());
    }

    public function test_only_author_can_delete_comment(): void
    {
        $comment = new Comment(['body' => 'Текст']);
        $comment->user()->associate($this->anna);
        $this->petrPainting->comments()->save($comment);

        $this->actingAs($this->petr)->delete("/comments/{$comment->id}")->assertForbidden();
        $this->actingAs($this->anna)->delete("/comments/{$comment->id}")->assertRedirect();
        $this->assertModelMissing($comment);
    }

    public function test_friendship_is_mutual_and_removed_both_ways(): void
    {
        $this->actingAs($this->anna)->post('/users/petr/friend')->assertRedirect();

        // Событие «обоюдной дружбы»: создана и обратная связь Пётр → Анна
        $this->assertTrue($this->anna->isFriendWith($this->petr));
        $this->assertTrue($this->petr->isFriendWith($this->anna));
        $this->assertDatabaseCount('friendships', 2);

        $this->actingAs($this->petr)->delete('/users/anna/friend')->assertRedirect();
        $this->assertFalse($this->anna->isFriendWith($this->petr));
        $this->assertFalse($this->petr->isFriendWith($this->anna));
        $this->assertDatabaseCount('friendships', 0);
    }

    public function test_cannot_befriend_yourself(): void
    {
        $this->actingAs($this->anna)->post('/users/anna/friend')->assertForbidden();
        $this->assertDatabaseCount('friendships', 0);
    }

    public function test_feed_shows_friends_paintings_newest_first(): void
    {
        $this->anna->friends()->attach($this->petr->id); // + обратная связь через событие
        $newer = $this->makePainting($this->petr, 'Новая картина Петра', now());
        $this->makePainting($this->ivan, 'Картина Ивана', now());

        $this->actingAs($this->anna)->get('/feed')
            ->assertOk()
            ->assertSeeInOrder([$newer->title, $this->petrPainting->title])
            ->assertDontSee('Картина Ивана')   // Иван — не друг
            ->assertDontSee('Картина Анны');   // свои картины в ленту не попадают

        $this->assertSame(
            [$newer->id, $this->petrPainting->id],
            $this->anna->feed()->pluck('id')->all()
        );
    }

    public function test_comments_from_friends_are_highlighted(): void
    {
        $this->anna->friends()->attach($this->petr->id);

        foreach ([[$this->petr, 'Комментарий друга'], [$this->ivan, 'Комментарий не друга']] as [$author, $body]) {
            $comment = new Comment(['body' => $body]);
            $comment->user()->associate($author);
            $this->annaPainting->comments()->save($comment);
        }

        $html = $this->actingAs($this->anna)->get("/paintings/{$this->annaPainting->id}")->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/comment-friend[^>]*>(?:(?!<article).)*Комментарий друга/s', $html);
        $this->assertDoesNotMatchRegularExpression('/comment-friend[^>]*>(?:(?!<article).)*Комментарий не друга/s', $html);

        // Иван не дружит с Петром — у него подсветки нет
        $this->actingAs($this->ivan)->get("/paintings/{$this->annaPainting->id}")->assertDontSee('comment-friend');
    }
}
