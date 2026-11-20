<?php

namespace App\Http\Controllers;

use App\Models\Painting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;

class PaintingController extends Controller
{
    // Список всех картин всех пользователей (GET /paintings)
    public function index()
    {
        $paintings = Painting::with('user')->withCount('comments')->latest()->get();
        return view('paintings.index', compact('paintings'));
    }

    // Форма добавления новой картины (GET /paintings/create)
    public function create()
    {
        Gate::authorize('create-painting');
        return view('paintings.create');
    }

    // Сохранение новой картины (POST /paintings).
    // user_id записывается в модели (событие creating) из текущего пользователя.
    public function store(Request $request)
    {
        Gate::authorize('create-painting');
        $data = $this->validateData($request);

        $data['image'] = $this->saveImage($request->file('image'));

        $painting = $request->user()->paintings()->create($data);

        return redirect()->route('users.show', $request->user())
            ->with('success', 'Картина «' . $painting->title . '» добавлена');
    }

    // Страница с подробной информацией о картине (GET /paintings/{painting})
    public function show(Painting $painting)
    {
        $painting->load(['user', 'comments.user']);

        // ID друзей текущего пользователя — для подсветки их комментариев
        $friendIds = auth()->user()?->friendIds() ?? [];

        return view('paintings.show', compact('painting', 'friendIds'));
    }

    // Форма редактирования (GET /paintings/{painting}/edit) — автор или администратор
    public function edit(Painting $painting)
    {
        Gate::authorize('update-painting', $painting);
        return view('paintings.edit', compact('painting'));
    }

    // Сохранение изменений (PUT /paintings/{painting}) — автор или администратор
    public function update(Request $request, Painting $painting)
    {
        Gate::authorize('update-painting', $painting);
        $data = $this->validateData($request, $painting);

        // Если выбран новый файл, заменяем изображение
        if ($request->hasFile('image')) {
            $data['image'] = $this->saveImage($request->file('image'));
        } else {
            unset($data['image']); // иначе остаётся старое изображение
        }

        $painting->update($data);

        return redirect()->route('paintings.show', $painting)->with('success', 'Картина обновлена');
    }

    // Проверка дубликата для формы (GET /paintings/check-duplicate?title=…&ignore=id)
    public function checkDuplicate(Request $request)
    {
        $title = trim((string) $request->query('title', ''));
        $ignore = $request->integer('ignore') ?: null;

        $exists = $title !== '' && Painting::isDuplicate($title, $ignore);

        return response()->json([
            'exists'  => $exists,
            'message' => $exists ? 'Картина с названием «' . $title . '» уже есть в галерее.' : null,
        ]);
    }

    // Мягкое удаление (DELETE /paintings/{painting}) — автор или администратор
    public function destroy(Painting $painting)
    {
        Gate::authorize('delete-painting', $painting);
        $painting->delete();

        return redirect()->route('users.show', $painting->user)->with('success', 'Картина удалена');
    }

    // Восстановление мягко удалённой картины (PATCH /paintings/{painting}/restore) — только администратор
    public function restore(Painting $painting)
    {
        Gate::authorize('restore-painting', $painting);
        $painting->restore();

        return back()->with('success', 'Картина «' . $painting->title . '» восстановлена');
    }

    // Удаление без возврата (DELETE /paintings/{painting}/force) — только администратор
    public function forceDelete(Painting $painting)
    {
        Gate::authorize('force-delete-painting', $painting);
        $image = $painting->image;
        $painting->forceDelete();

        if ($image && ! Painting::withTrashed()->where('image', $image)->exists()) {
            Storage::disk('public')->delete($image);
        }

        return back()->with('success', 'Картина удалена навсегда');
    }

        // Изменение размера изображения под вёрстку (600×400) и сохранение
    private function saveImage(UploadedFile $file): string
    {
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->decodePath($file->getRealPath());
            $image->cover(600, 400); // обрезка и масштабирование под карточку

            // JPEG, если GD его поддерживает, иначе PNG
            $jpeg = (bool) (imagetypes() & IMG_JPG);
            $path = 'paintings/' . uniqid() . ($jpeg ? '.jpg' : '.png');
            $encoded = $jpeg ? $image->encode(new JpegEncoder(quality: 85)) : $image->encode(new PngEncoder());
            Storage::disk('public')->put($path, (string) $encoded);

            return $path;
        } catch (\Throwable $e) {
            // Формат не поддерживается драйвером GD — сохраняем оригинал без обработки
            report($e);

            return $file->store('paintings', 'public');
        }
    }

    // Правила валидации, общие для store и update
    private function validateData(Request $request, ?Painting $painting = null): array
    {
        // Изображение обязательно при создании и если у картины ещё нет файла
        $imageRequired = ! $painting || ! $painting->image
            || ! Storage::disk('public')->exists($painting->image);

        $rules = [
            'title'       => [
                'required', 'string', 'max:255',
                // Запрет дубликатов: картина с таким названием уже есть
                function (string $attribute, mixed $value, \Closure $fail) use ($painting) {
                    if (is_string($value) && Painting::isDuplicate($value, $painting?->id)) {
                        $fail('Картина с названием «' . trim($value) . '» уже есть в галерее.');
                    }
                },
            ],
            'author'      => 'required|string|max:255',
            'year'        => 'required|integer|min:1000|max:' . date('Y'), // год не может быть в будущем
            'technique'   => ['required', Rule::in(Painting::TECHNIQUES)],
            'size'        => 'required|string|max:100',
            'location'    => 'required|string|max:255',
            'description' => 'required|string',
            'image'       => [$imageRequired ? 'required' : 'nullable', 'image', 'max:2048'],
        ];

        // Сообщения об ошибках на русском языке
        $messages = [
            'required'  => 'Поле «:attribute» обязательно для заполнения.',
            'string'    => 'Поле «:attribute» должно быть текстом.',
            'max'       => 'Поле «:attribute» слишком длинное (максимум :max).',
            'integer'   => 'Поле «:attribute» должно быть целым числом.',
            'year.min'  => 'Год не может быть меньше :min.',
            'year.max'  => 'Год не может быть больше :max (картина не может быть из будущего).',
            'technique.in'   => 'Выберите технику из списка.',
            'image.required' => 'Загрузите изображение картины — без фото сохранить нельзя.',
            'image'     => 'Файл должен быть изображением.',
            'image.max' => 'Размер изображения не должен превышать 2 МБ.',
        ];

        // Русские названия полей для сообщений
        $attributes = [
            'title'       => 'Название',
            'author'      => 'Автор',
            'year'        => 'Год',
            'technique'   => 'Техника',
            'size'        => 'Размер',
            'location'    => 'Где хранится',
            'description' => 'Описание',
            'image'       => 'Изображение',
        ];

        return $request->validate($rules, $messages, $attributes);
    }
}
