@php
    $fields = [
        'title' => 'Название', 'author' => 'Автор', 'year' => 'Год',
        'technique' => 'Техника', 'size' => 'Размер', 'location' => 'Где хранится',
    ];
    $currentTechnique = old('technique', $painting->technique ?? '');
    // Фото обязательно, если у картины ещё нет изображения (создание или картина без фото)
    $imageRequired = ! isset($painting) || ! $painting->image
        || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($painting->image);
@endphp

@foreach ($fields as $name => $label)
    <div class="mb-3">
        <label for="{{ $name }}" class="form-label">{{ $label }}</label>
        @if ($name === 'technique')
            <select id="technique" name="technique" required
                    class="form-select @error('technique') is-invalid @enderror">
                <option value="" disabled @selected($currentTechnique === '')>— Выберите технику —</option>
                @foreach (\App\Models\Painting::TECHNIQUES as $technique)
                    <option value="{{ $technique }}" @selected($currentTechnique === $technique)>{{ $technique }}</option>
                @endforeach
            </select>
            @error('technique')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @continue
        @endif
        <input type="{{ $name === 'year' ? 'number' : 'text' }}" id="{{ $name }}" name="{{ $name }}"
               class="form-control @error($name) is-invalid @enderror"
               value="{{ old($name, $painting->$name ?? '') }}" required
               @if ($name === 'year') min="1000" max="{{ date('Y') }}" step="1" @endif>
        @error($name)
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if ($name === 'title')
            {{-- Сообщение о дубликате, заполняется JavaScript при вводе --}}
            <div class="invalid-feedback" id="duplicate-feedback"></div>
        @endif
    </div>
@endforeach

<div class="mb-3">
    <label for="description" class="form-label">Описание</label>
    <textarea id="description" name="description" rows="4"
              class="form-control @error('description') is-invalid @enderror" required>{{ old('description', $painting->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="image" class="form-label">Изображение</label>
    <input type="file" id="image" name="image" accept="image/*" @required($imageRequired)
           class="form-control @error('image') is-invalid @enderror">
    @unless ($imageRequired)
        <div class="form-text">Оставьте пустым, чтобы сохранить текущее изображение.</div>
    @endunless
    @error('image')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button type="submit" class="btn btn-primary" id="save-button">Сохранить</button>
<a href="{{ route('paintings.index') }}" class="btn btn-secondary">Отмена</a>

<script>
// Проверка дубликата «на лету»: при вводе названия/автора спрашиваем сервер
(function () {
    const title = document.getElementById('title');
    const feedback = document.getElementById('duplicate-feedback');
    const saveButton = document.getElementById('save-button');
    const url = @json(route('paintings.check-duplicate'));
    const ignoreId = @json(isset($painting) ? $painting->id : null);
    let timer = null;
    let lastRequest = 0;

    function setDuplicate(message) {
        const isDuplicate = Boolean(message);
        feedback.textContent = message || '';
        title.classList.toggle('is-invalid', isDuplicate);
        title.classList.toggle('is-valid', !isDuplicate && title.value.trim() !== '');
        saveButton.disabled = isDuplicate;
        title.setCustomValidity(message || '');
    }

    async function check() {
        const t = title.value.trim();
        if (t === '') { setDuplicate(null); return; }

        const params = new URLSearchParams({ title: t });
        if (ignoreId) params.set('ignore', ignoreId);

        const requestId = ++lastRequest;
        try {
            const response = await fetch(url + '?' + params, { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (requestId === lastRequest) setDuplicate(data.exists ? data.message : null);
        } catch (e) {
            // Сеть недоступна — проверка всё равно выполнится на сервере при сохранении
        }
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(check, 300);
    }

    title.addEventListener('input', schedule);
    if (title.value) check();
})();
</script>