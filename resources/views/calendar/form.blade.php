@extends('layouts.app')

@section('title', $calendarReminder->exists ? 'Editar recordatorio' : 'Nuevo recordatorio')
@section('page-title', $calendarReminder->exists ? 'Editar recordatorio' : 'Nuevo recordatorio')

@section('content')
@php
    $selectedColor = old('color', $calendarReminder->color ?: '#E31B23');
    $colorOptions = [
        '#E31B23' => 'Rojo',
        '#2563EB' => 'Azul',
        '#16A34A' => 'Verde',
        '#D97706' => 'Ambar',
        '#7C3AED' => 'Violeta',
        '#0891B2' => 'Cian',
    ];
    $isCustomColor = ! array_key_exists($selectedColor, $colorOptions);
@endphp

@include('shared.validation')

<section class="reminder-editor">
    <div class="reminder-editor-heading">
        <a class="back-link" href="{{ route('calendar.index', ['month' => $calendarReminder->event_date?->format('Y-m') ?: now()->format('Y-m')]) }}">
            <i class="bi bi-arrow-left"></i> Volver a la agenda
        </a>
        <div class="reminder-editor-title">
            <div class="reminder-editor-icon" style="--reminder-color: {{ $selectedColor }}"><i class="bi bi-calendar-event"></i></div>
            <div>
                <p class="eyebrow">{{ $calendarReminder->exists ? 'Editar evento' : 'Nuevo evento' }}</p>
                <h2>{{ $calendarReminder->exists ? 'Edita tu recordatorio' : 'Crea un recordatorio' }}</h2>
                <p>Organiza actividades y mantén visible lo importante en tu agenda.</p>
            </div>
        </div>
        @if($calendarReminder->exists)
            <span class="reminder-editor-state {{ $calendarReminder->is_done ? 'is-done' : '' }}">
                <i class="bi {{ $calendarReminder->is_done ? 'bi-check-circle' : 'bi-clock' }}"></i>
                {{ $calendarReminder->is_done ? 'Completado' : 'Pendiente' }}
            </span>
        @endif
    </div>

    <form class="reminder-editor-form" method="POST" action="{{ route('calendar.'.($calendarReminder->exists ? 'update' : 'store'), $calendarReminder->exists ? $calendarReminder : []) }}">
        @csrf
        @if($calendarReminder->exists) @method('PUT') @endif

        <div class="reminder-editor-main">
            <section class="reminder-form-section">
                <div class="reminder-section-heading">
                    <span class="reminder-section-icon"><i class="bi bi-pencil-square"></i></span>
                    <div><h3>Información del evento</h3><p>Define cuándo y qué necesitas recordar.</p></div>
                </div>
                <div class="reminder-fields-grid">
                    <label>
                        <span>Fecha <b>*</b></span>
                        <span class="input-with-icon"><i class="bi bi-calendar3"></i><input type="date" name="event_date" required value="{{ old('event_date', $calendarReminder->event_date?->format('Y-m-d')) }}"></span>
                    </label>
                    <label>
                        <span>Título <b>*</b></span>
                        <span class="input-with-icon"><i class="bi bi-type"></i><input name="title" required maxlength="255" value="{{ old('title', $calendarReminder->title) }}" placeholder="Ej. Revisión de inventario"></span>
                    </label>
                </div>
                <label class="reminder-textarea-field">
                    <span>Descripción</span>
                    <textarea name="description" placeholder="Agrega contexto, instrucciones o ubicación">{{ old('description', $calendarReminder->description) }}</textarea>
                </label>
                <label class="reminder-textarea-field">
                    <span>Comentarios</span>
                    <textarea name="comments" placeholder="Notas adicionales (opcional)">{{ old('comments', $calendarReminder->comments) }}</textarea>
                </label>
            </section>

            <aside class="reminder-form-side">
                <section class="reminder-form-section">
                    <div class="reminder-section-heading">
                        <span class="reminder-section-icon"><i class="bi bi-palette"></i></span>
                        <div><h3>Color del evento</h3><p>Identifícalo rápidamente en el calendario.</p></div>
                    </div>
                    <div class="reminder-color-options" role="radiogroup" aria-label="Color del evento">
                        @foreach($colorOptions as $color => $label)
                            <label class="reminder-color-option" title="{{ $label }}">
                                <input type="radio" name="color" value="{{ $color }}" @checked($selectedColor === $color)>
                                <span style="--swatch-color: {{ $color }}"><i class="bi bi-check-lg"></i><b>{{ $label }}</b></span>
                            </label>
                        @endforeach
                        <label class="reminder-color-option reminder-custom-color-option" title="Personalizado">
                            <input type="radio" name="color" value="{{ $selectedColor }}" data-custom-color-radio @checked($isCustomColor)>
                            <span class="reminder-custom-color-swatch" style="--swatch-color: {{ $isCustomColor ? $selectedColor : '#64748B' }}">
                                <i class="bi bi-check-lg"></i><b>Personalizado</b><small data-custom-color-value>{{ $isCustomColor ? $selectedColor : 'Elegir color' }}</small>
                                <input type="color" value="{{ $isCustomColor ? $selectedColor : '#64748B' }}" data-custom-color aria-label="Elegir color personalizado">
                            </span>
                        </label>
                    </div>
                    <div class="reminder-color-preview" data-reminder-preview style="--reminder-color: {{ $selectedColor }}">
                        <i class="bi bi-calendar-event"></i>
                        <span><strong data-reminder-preview-title>{{ old('title', $calendarReminder->title) ?: 'Tu recordatorio' }}</strong><small>Así se verá en tu agenda</small></span>
                    </div>
                </section>

                <section class="reminder-form-section reminder-complete-section">
                    <label class="reminder-check-option">
                        <input type="checkbox" name="is_done" value="1" @checked(old('is_done', $calendarReminder->is_done))>
                        <span><i class="bi bi-check2"></i></span>
                        <b>Marcar como completado</b>
                    </label>
                </section>
            </aside>
        </div>

        <footer class="reminder-editor-footer">
            <a class="button button-secondary" href="{{ route('calendar.index', ['month' => $calendarReminder->event_date?->format('Y-m') ?: now()->format('Y-m')]) }}">Cancelar</a>
            @if($calendarReminder->exists)
                @can('delete', $calendarReminder)
                    <button class="button button-danger" form="delete-reminder" type="submit"><i class="bi bi-trash3"></i> Eliminar</button>
                @endcan
            @endif
            <button class="button button-primary" type="submit"><i class="bi bi-check2"></i> {{ $calendarReminder->exists ? 'Guardar cambios' : 'Guardar evento' }}</button>
        </footer>
    </form>
</section>

@if($calendarReminder->exists)
    @can('delete', $calendarReminder)
        <form id="delete-reminder" method="POST" action="{{ route('calendar.destroy', $calendarReminder) }}">@csrf @method('DELETE')</form>
    @endcan
@endif
@endsection

@push('scripts')
<script>
(() => {
    const colorInputs = document.querySelectorAll('input[name="color"]');
    const preview = document.querySelector('[data-reminder-preview]');
    const titleInput = document.querySelector('input[name="title"]');
    const previewTitle = document.querySelector('[data-reminder-preview-title]');
    const customColor = document.querySelector('[data-custom-color]');
    const customColorRadio = document.querySelector('[data-custom-color-radio]');
    const customColorValue = document.querySelector('[data-custom-color-value]');
    const updatePreview = () => {
        const color = document.querySelector('input[name="color"]:checked')?.value || '#E31B23';
        preview?.style.setProperty('--reminder-color', color);
        if (previewTitle) previewTitle.textContent = titleInput?.value.trim() || 'Tu recordatorio';
    };
    colorInputs.forEach((input) => input.addEventListener('change', updatePreview));
    customColor?.addEventListener('input', () => {
        if (customColorRadio) {
            customColorRadio.value = customColor.value.toUpperCase();
            customColorRadio.checked = true;
        }
        customColor.closest('.reminder-custom-color-swatch')?.style.setProperty('--swatch-color', customColor.value);
        if (customColorValue) customColorValue.textContent = customColor.value.toUpperCase();
        updatePreview();
    });
    customColorRadio?.addEventListener('change', () => {
        updatePreview();
        window.setTimeout(() => customColor?.click(), 0);
    });
    titleInput?.addEventListener('input', updatePreview);
})();
</script>
@endpush
