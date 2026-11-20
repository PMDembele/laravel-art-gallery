<?php

namespace Tests\Feature;

use App\Models\Painting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// ЛР4: проверка прав доступа к картинам (маршруты, Gates, события модели)
class PaintingAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $other;
    private User $admin;
    private Painting $painting;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::disk('public')->put('paintings/test.jpg', 'fake'); // у картины уже есть изображение

        $this->owner = User::factory()->create(['username' => 'owner']);
        $this->other = User::factory()->create(['username' => 'other']);
        $this->admin = User::factory()->create(['username' => 'boss']);
        $this->admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($this->owner);
        $this->painting = $this->owner->paintings()->create($this->data(['title' => 'Картина владельца']));
        auth()->logout();
    }

    private function data(array $override = []): array
    {
        return array_merge([
            'title' => 'Новая картина',
            'author' => 'Художник',
            'year' => 1900,
            'technique' => 'Акварель',
            'size' => '30 × 40 см',
            'location' => 'Музей',
            'description' => 'Описание',
            'image' => 'paintings/test.jpg',
        ], $override);
    }

    // Данные формы редактирования: без поля image (файл не меняется)
    private function formData(array $override = []): array
    {
        return array_diff_key($this->data($override), ['image' => true]);
    }

    public function test_guest_can_view_gallery_but_cannot_modify(): void
    {
        $this->get('/paintings')->assertOk()->assertSee('Картина владельца');
        $this->get('/paintings/create')->assertRedirect('/login');
        $this->delete("/paintings/{$this->painting->id}")->assertRedirect('/login');
        $this->get('/users/owner')->assertRedirect('/login');
    }

    public function test_store_saves_current_user_id(): void
    {
        $payload = $this->data(['image' => UploadedFile::fake()->image('p.jpg', 1200, 900)]);

        $this->actingAs($this->other)->post('/paintings', $payload)->assertRedirect('/users/other');

        $created = Painting::where('title', 'Новая картина')->firstOrFail();
        $this->assertSame($this->other->id, $created->user_id);
        Storage::disk('public')->assertExists($created->image);
    }

    public function test_user_page_uses_username_and_lists_own_paintings(): void
    {
        $this->actingAs($this->other)->get('/users/owner')
            ->assertOk()->assertSee('Картина владельца');
        $this->actingAs($this->other)->get('/users/' . $this->owner->id)->assertNotFound();
    }

    public function test_only_owner_or_admin_can_edit(): void
    {
        $this->actingAs($this->other)->get("/paintings/{$this->painting->id}/edit")->assertForbidden();
        $this->actingAs($this->other)->put("/paintings/{$this->painting->id}", $this->formData(['title' => 'Взлом']))
            ->assertForbidden();

        $this->actingAs($this->owner)->get("/paintings/{$this->painting->id}/edit")->assertOk();
        $this->actingAs($this->admin)->put("/paintings/{$this->painting->id}", $this->formData(['title' => 'Правка админа']))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Правка админа', $this->painting->fresh()->title);
    }

    public function test_buttons_are_hidden_without_rights(): void
    {
        $this->actingAs($this->other)->get('/paintings')->assertDontSee('Изменить');
        $this->actingAs($this->owner)->get('/paintings')->assertSee('Изменить');
    }

    public function test_owner_soft_deletes_and_only_admin_restores_or_force_deletes(): void
    {
        $this->actingAs($this->other)->delete("/paintings/{$this->painting->id}")->assertForbidden();

        $this->actingAs($this->owner)->delete("/paintings/{$this->painting->id}")->assertRedirect();
        $this->assertSoftDeleted($this->painting);

        // Владелец видит удалённую картину в своём списке, но восстановить не может
        $this->actingAs($this->owner)->get('/users/owner')->assertSee('Удалена');
        $this->actingAs($this->owner)->patch("/paintings/{$this->painting->id}/restore")->assertForbidden();
        $this->actingAs($this->owner)->delete("/paintings/{$this->painting->id}/force")->assertForbidden();
        // Другой пользователь удалённые картины не видит
        $this->actingAs($this->other)->get('/users/owner')->assertDontSee('Картина владельца');

        $this->actingAs($this->admin)->patch("/paintings/{$this->painting->id}/restore")->assertRedirect();
        $this->assertNotSoftDeleted($this->painting);

        $this->painting->delete(); // консоль без пользователя — модель разрешает
        $this->actingAs($this->admin)->delete("/paintings/{$this->painting->id}/force")->assertRedirect();
        $this->assertDatabaseMissing('paintings', ['id' => $this->painting->id]);
    }

    public function test_model_events_block_foreign_changes_even_without_controller(): void
    {
        $this->actingAs($this->other);

        $this->expectException(AuthorizationException::class);
        $this->painting->update(['title' => 'Изменено в обход контроллера']);
    }

    public function test_users_index_lists_all_users(): void
    {
        $this->actingAs($this->owner)->get('/users')
            ->assertOk()->assertSee('@owner')->assertSee('@other')->assertSee('@boss');
    }
}
