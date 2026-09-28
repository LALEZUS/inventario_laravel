@extends('layouts.app')

@section('title', 'Agenda')
@section('page-title', 'Agenda global')

@section('content')
@php
    $gridStart = $start->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
    $gridEnd = $start->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);
    $today = now()->format('Y-m-d');
@endphp

<section class="page-intro compact agenda-page-heading">
    <div>
        <p class="eyebrow">Agenda global</p>
        <h2>{{ ucfirst($start->translatedFormat('F Y')) }}</h2>
        <p>{{ $items->flatten()->count() }} recordatorios este mes.</p>
    </div>
    <div class="page-actions">
        <a class="button button-secondary" href="{{ route('calendar.index', ['month' => $start->copy()->subMonth()->format('Y-m')]) }}">Anterior</a>
        <a class="button button-secondary" href="{{ route('calendar.index', ['month' => now()->format('Y-m')]) }}">Hoy</a>
        <a class="button button-secondary" href="{{ route('calendar.index', ['month' => $start->copy()->addMonth()->format('Y-m')]) }}">Siguiente</a>
        @can('create', \App\Models\CalendarReminder::class)
            <button class="button button-primary" type="button" data-open-agenda-modal data-date="{{ now()->format('Y-m-d') }}">Nuevo recordatorio</button>
        @endcan
    </div>
</section>

<section class="agenda-calendar-layout">
    <div class="panel agenda-calendar-panel">
        <div class="agenda-calendar-top">
            <div>
                <p class="eyebrow">Calendario</p>
                <h3>{{ ucfirst($start->translatedFormat('F Y')) }}</h3>
            </div>
            <span class="agenda-month-count"><i class="bi bi-calendar3"></i> {{ $items->flatten()->count() }} eventos</span>
        </div>

        <div class="agenda-weekdays" aria-hidden="true">
            @foreach(['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $weekday)
                <span>{{ $weekday }}</span>
            @endforeach
        </div>

        <div class="agenda-calendar-grid" role="grid" aria-label="Calendario de {{ $start->translatedFormat('F Y') }}">
            @for($date = $gridStart->copy(); $date->lte($gridEnd); $date->addDay())
                @php
                    $dateKey = $date->format('Y-m-d');
                    $dayItems = $items->get($dateKey, collect());
                @endphp
                <button
                    type="button"
                    class="agenda-calendar-day {{ $date->month !== $start->month ? 'is-outside' : '' }} {{ $dateKey === $today ? 'is-today' : '' }} {{ $dayItems->isNotEmpty() ? 'has-events' : '' }}"
                    style="--event-color: {{ $dayItems->first()->color ?? '#E31B23' }}"
                    data-{{ $dayItems->isNotEmpty() ? 'open-agenda-day' : 'open-agenda-modal' }}
                    data-date="{{ $dateKey }}"
                    role="gridcell"
                    aria-label="{{ $date->translatedFormat('l d \d\e F Y') }}"
                >
                    <time datetime="{{ $dateKey }}">{{ $date->day }}</time>
                    @if($dayItems->isNotEmpty())
                        <span class="agenda-day-event-count">{{ $dayItems->count() }}</span>
                        <span class="agenda-day-dots" aria-hidden="true">
                            @foreach($dayItems->take(3) as $dayItem)<i style="--dot-color: {{ $dayItem->color ?: '#E31B23' }}"></i>@endforeach
                        </span>
                    @endif
                </button>
            @endfor
        </div>

        <p class="agenda-calendar-help"><i class="bi bi-info-circle"></i> Selecciona cualquier día para agendar un recordatorio.</p>
    </div>

    <aside class="panel agenda-upcoming-panel">
        <div class="agenda-calendar-top">
            <div>
                <p class="eyebrow">Seguimiento</p>
                <h3>Próximos eventos</h3>
            </div>
            <span class="agenda-month-count">{{ $upcoming->count() }}</span>
        </div>

        <div class="agenda-upcoming-list">
            @forelse($upcoming as $item)
                <a class="agenda-upcoming-item {{ $item->is_done ? 'is-done' : '' }}" style="--event-color: {{ $item->color ?: '#E31B23' }}" href="{{ route('calendar.edit', $item) }}">
                    <time datetime="{{ $item->event_date->format('Y-m-d') }}">{{ $item->event_date->translatedFormat('D d M') }}</time>
                    <span>
                        <strong>{{ $item->title }}</strong>
                        <small>{{ $item->description ?: 'Sin descripción' }}</small>
                    </span>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
            @empty
                <p class="empty">No hay eventos próximos.</p>
            @endforelse
        </div>

        @can('create', \App\Models\CalendarReminder::class)
            <button class="button button-secondary agenda-upcoming-add" type="button" data-open-agenda-modal data-date="{{ now()->format('Y-m-d') }}">
                <i class="bi bi-plus-lg"></i> Agregar evento
            </button>
        @endcan
    </aside>
</section>

@can('create', \App\Models\CalendarReminder::class)
    <div class="agenda-modal" data-agenda-day-modal hidden>
        <div class="agenda-modal-backdrop" data-close-agenda-day-modal></div>
        <section class="agenda-modal-card agenda-day-modal-card" aria-labelledby="agenda-day-modal-title">
            <header>
                <div>
                    <p class="eyebrow">Agenda del día</p>
                    <h2 id="agenda-day-modal-title" data-agenda-day-title>Recordatorios</h2>
                </div>
                <button class="agenda-modal-close" type="button" data-close-agenda-day-modal aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
            </header>
            <div class="agenda-day-modal-list" data-agenda-day-list></div>
            <footer>
                <button class="button button-secondary" type="button" data-close-agenda-day-modal>Cerrar</button>
                <button class="button button-primary" type="button" data-open-agenda-modal-from-day><i class="bi bi-plus-lg"></i> Nuevo recordatorio</button>
            </footer>
        </section>
    </div>
    @foreach($items as $date => $dayItems)
        <template data-agenda-day-template="{{ $date }}">
            @foreach($dayItems as $item)
                <a class="agenda-day-modal-item {{ $item->is_done ? 'is-done' : '' }}" style="--event-color: {{ $item->color ?: '#E31B23' }}" href="{{ route('calendar.edit', $item) }}">
                    <span class="agenda-day-modal-status">{{ $item->is_done ? 'Hecho' : 'Pendiente' }}</span>
                    <span>
                        <strong>{{ $item->title }}</strong>
                        <small>{{ $item->description ?: 'Sin descripción' }}</small>
                    </span>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
            @endforeach
        </template>
    @endforeach
    <div class="agenda-modal" data-agenda-modal hidden>
        <div class="agenda-modal-backdrop" data-close-agenda-modal></div>
        <form class="agenda-modal-card" method="POST" action="{{ route('calendar.store') }}">
            @csrf
            <header>
                <div>
                    <p class="eyebrow">Agenda global</p>
                    <h2>Nuevo recordatorio</h2>
                </div>
                <button class="agenda-modal-close" type="button" data-close-agenda-modal aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
            </header>

            <div class="agenda-modal-fields">
                <label>
                    <span>Fecha</span>
                    <input type="date" name="event_date" data-agenda-date required>
                </label>
                <label>
                    <span>Título</span>
                    <input type="text" name="title" data-agenda-title maxlength="255" placeholder="Ej. Revisión de inventario" required>
                </label>
                <label class="agenda-modal-color-field">
                    <span>Color</span>
                    <span class="agenda-modal-color-control">
                        <input type="color" name="color" value="#E31B23" data-agenda-color aria-label="Elegir color del evento">
                    </span>
                </label>
            </div>
            <label class="textarea-field">
                <span>Descripción</span>
                <textarea name="description" placeholder="Agrega contexto o instrucciones"></textarea>
            </label>
            <label class="textarea-field">
                <span>Comentarios</span>
                <textarea name="comments" placeholder="Notas adicionales (opcional)"></textarea>
            </label>
            <footer>
                <button class="button button-secondary" type="button" data-close-agenda-modal>Cancelar</button>
                <button class="button button-primary" type="submit"><i class="bi bi-check2"></i> Guardar evento</button>
            </footer>
        </form>
    </div>
@endcan
@endsection

@push('scripts')
<script>
(() => {
    const modal = document.querySelector('[data-agenda-modal]');
    const dayModal = document.querySelector('[data-agenda-day-modal]');
    if (!modal || !dayModal) return;
    const dateInput = modal.querySelector('[data-agenda-date]');
    const titleInput = modal.querySelector('[data-agenda-title]');
    const dayTitle = dayModal.querySelector('[data-agenda-day-title]');
    const dayList = dayModal.querySelector('[data-agenda-day-list]');
    let selectedDate = '';
    const openModal = (date) => {
        selectedDate = date || selectedDate;
        if (date) dateInput.value = date;
        dayModal.hidden = true;
        modal.hidden = false;
        document.body.classList.add('modal-open');
        window.setTimeout(() => titleInput.focus(), 40);
    };
    const closeModal = () => {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
    };
    const closeDayModal = () => {
        dayModal.hidden = true;
        document.body.classList.remove('modal-open');
    };
    const openDayModal = (date) => {
        selectedDate = date;
        const template = document.querySelector('[data-agenda-day-template="' + date + '"]');
        if (!template) return;
        dayList.replaceChildren(template.content.cloneNode(true));
        const day = new Date(date + 'T12:00:00');
        dayTitle.textContent = new Intl.DateTimeFormat('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(day);
        modal.hidden = true;
        dayModal.hidden = false;
        document.body.classList.add('modal-open');
    };
    document.querySelectorAll('[data-open-agenda-modal]').forEach((button) => {
        button.addEventListener('click', () => openModal(button.dataset.date || ''));
    });
    document.querySelectorAll('[data-open-agenda-day]').forEach((button) => {
        button.addEventListener('click', () => openDayModal(button.dataset.date || ''));
    });
    modal.querySelectorAll('[data-close-agenda-modal]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });
    dayModal.querySelectorAll('[data-close-agenda-day-modal]').forEach((button) => {
        button.addEventListener('click', closeDayModal);
    });
    dayModal.querySelector('[data-open-agenda-modal-from-day]').addEventListener('click', () => openModal(selectedDate));
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (!modal.hidden) closeModal();
        if (!dayModal.hidden) closeDayModal();
    });
})();
</script>
@endpush
