@extends('layouts.app')

@section('title', 'Panel de control')
@section('page-title', 'Panel de Control')

@section('content')
    <section class="dashboard-welcome">
        <div>
            <h2>{{ $greeting }}, {{ auth()->user()->display_name }} <span class="dashboard-wave" aria-hidden="true">👋</span></h2>
            <p>Gestion de activos e indicadores criticos</p>
        </div>
        <form class="dashboard-global-search" method="GET" action="{{ route('search.index') }}" role="search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <label class="sr-only" for="dashboard-search">Buscar en todo el inventario</label>
            <input id="dashboard-search" name="q" minlength="2" maxlength="100" placeholder="Buscar en todo el inventario...">
        </form>
        <a class="dashboard-register-action" href="{{ route('computers.create') }}"><i class="bi bi-plus-lg"></i><span>Registrar activo</span><i class="bi bi-chevron-down"></i></a>
    </section>

    <section class="dashboard-kpis" aria-label="Indicadores principales">
        <a href="{{ route('computers.index') }}"><i class="bi bi-display kpi-icon"></i><span><small>Equipos</small><strong>{{ $stats['computers'] }}</strong><em>Activos registrados</em></span><i class="bi bi-chevron-right kpi-arrow"></i></a>
        <a href="{{ route('printers.index') }}"><i class="bi bi-printer kpi-icon"></i><span><small>Impresoras</small><strong>{{ $stats['printers'] }}</strong><em>En inventario</em></span><i class="bi bi-chevron-right kpi-arrow"></i></a>
        <a href="{{ route('credentials.index', ['type' => 'licenses']) }}"><i class="bi bi-shield-check kpi-icon"></i><span><small>Licencias</small><strong>{{ $stats['active_licenses'] }}</strong><em>Vigentes</em></span><i class="bi bi-chevron-right kpi-arrow"></i></a>
        <a href="{{ route('tutorials.index') }}"><i class="bi bi-file-earmark-text kpi-icon"></i><span><small>Guias</small><strong>{{ $stats['tutorials'] }}</strong><em>Disponibles</em></span><i class="bi bi-chevron-right kpi-arrow"></i></a>
    </section>

    <section class="dashboard-primary-grid">
        <article class="dashboard-panel calendar-panel">
            <header class="dashboard-panel-header">
                <h3><i class="bi bi-calendar3"></i>Agenda Global</h3>
                <div class="calendar-header-actions"><a class="calendar-month-link" href="{{ route('calendar.index', ['month' => $calendarMonth->format('Y-m')]) }}">{{ ucfirst($calendarMonth->translatedFormat('F Y')) }}</a><a class="calendar-today-link" href="{{ route('calendar.index') }}">Hoy</a></div>
            </header>
            <div class="agenda-layout">
                <div class="dashboard-calendar" aria-label="Calendario de {{ $calendarMonth->translatedFormat('F Y') }}">
                    @foreach (['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'] as $weekday)
                        <span class="calendar-weekday">{{ $weekday }}</span>
                    @endforeach
                    @php $gridStart = $calendarMonth->copy()->startOfWeek(\Carbon\Carbon::SUNDAY); @endphp
                    @for ($index = 0; $index < 42; $index++)
                        @php
                            $day = $gridStart->copy()->addDays($index);
                            $dayItems = $calendarReminders->get($day->format('Y-m-d'), collect());
                        @endphp
                        @can('create', \App\Models\CalendarReminder::class)
                            <button type="button" class="calendar-cell {{ $day->month !== $calendarMonth->month ? 'outside' : '' }} {{ $day->isToday() ? 'today' : '' }}" data-{{ $dayItems->isNotEmpty() ? 'dashboard-open-agenda-day' : 'dashboard-open-agenda-modal' }} data-date="{{ $day->format('Y-m-d') }}" title="{{ $dayItems->isEmpty() ? 'Agregar recordatorio' : $dayItems->pluck('title')->join(', ') }}"><time datetime="{{ $day->format('Y-m-d') }}">{{ $day->day }}</time>@if ($dayItems->isNotEmpty())<b>{{ $dayItems->count() }}</b>@endif</button>
                        @else
                            <a href="{{ route('calendar.index', ['month' => $day->format('Y-m')]) }}" class="calendar-cell {{ $day->month !== $calendarMonth->month ? 'outside' : '' }} {{ $day->isToday() ? 'today' : '' }}" title="{{ $dayItems->pluck('title')->join(', ') }}"><time datetime="{{ $day->format('Y-m-d') }}">{{ $day->day }}</time>@if ($dayItems->isNotEmpty())<b>{{ $dayItems->count() }}</b>@endif</a>
                        @endcan
                    @endfor
                </div>
                <aside class="agenda-events"><h4>Eventos del dia <b>{{ $calendarReminders->flatten(1)->count() }}</b></h4>@forelse ($calendarReminders->flatten(1)->sortBy('event_date')->take(3) as $reminder)<button type="button" class="dashboard-agenda-event" data-dashboard-open-agenda-day data-date="{{ $reminder->event_date->format('Y-m-d') }}"><time>{{ $reminder->event_date->format('H:i') }}</time><span><strong>{{ $reminder->title }}</strong><small>{{ $reminder->description ?: 'Agenda global' }}</small></span></button>@empty<p>No hay eventos programados.</p>@endforelse<a class="agenda-more" href="{{ route('calendar.index') }}"><i class="bi bi-calendar3"></i> Ver agenda completa</a></aside>
            </div>
        </article>

        <article class="dashboard-panel alerts-panel">
            <header class="dashboard-panel-header">
                <h3><i class="bi bi-bell"></i>Centro de Alertas</h3>
                <span>{{ $alerts->count() }}</span>
            </header>
            <div class="dashboard-alert-list">
                @forelse ($alerts as $alert)
                    <a href="{{ $alert['url'] }}" class="alert-row {{ $alert['tone'] }}">
                        <i class="bi {{ $alert['icon'] }}" aria-hidden="true"></i>
                        <span><strong>{{ $alert['title'] }}</strong><small>{{ $alert['detail'] }}</small></span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                @empty
                    <div class="dashboard-empty"><i class="bi bi-check-circle"></i><strong>Todo en orden</strong><span>No hay alertas activas.</span></div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="dashboard-module-strip" aria-label="Resumen de modulos">
        @foreach ([
            ['Celulares', $stats['cellphones'], 'bi-phone', route('cellphones.index')],
            ['Perifericos', $stats['peripherals'], 'bi-mouse2', route('peripherals.index')],
            ['Empleados', $stats['employees'], 'bi-people', route('employees.index')],
            ['Red', $stats['network_devices'], 'bi-router', route('network.index')],
            ['Credenciales', $stats['credentials'], 'bi-shield-lock', route('credentials.index')],
            ['Correos', $stats['emails'], 'bi-envelope', route('emails.index')],
        ] as [$label, $value, $icon, $url])
            <a href="{{ $url }}"><i class="bi {{ $icon }}"></i><span>{{ $label }}<strong>{{ $value }}</strong></span></a>
        @endforeach
    </section>

    <section class="dashboard-secondary-grid">
        <article class="dashboard-panel">
                <header class="dashboard-panel-header"><h3><i class="bi bi-journal-bookmark"></i>Notas recientes</h3><a href="{{ route('notes.index') }}">Ver todas <i class="bi bi-chevron-right"></i></a></header>
            <div class="compact-activity-list">
                @forelse ($recentNotes as $note)
                    <a href="{{ route('notes.show', $note) }}"><span><strong>{{ $note->title }}</strong><small>{{ $note->updated_at?->format('d/m/Y') }}</small></span><i class="bi bi-arrow-up-right"></i></a>
                @empty <p class="empty">No hay notas registradas.</p> @endforelse
            </div>
        </article>
        <article class="dashboard-panel">
            <header class="dashboard-panel-header"><h3><i class="bi bi-display"></i>Equipos actualizados</h3><a href="{{ route('computers.index') }}">Ver todos <i class="bi bi-chevron-right"></i></a></header>
            <div class="compact-activity-list">
                @foreach ($recentComputers->take(5) as $computer)
                    <a href="{{ route('computers.show', $computer) }}"><span><strong>{{ $computer->name }}</strong><small>{{ $computer->assigned_to ?: 'Sin usuario' }}</small></span><em class="status">{{ $computer->status }}</em></a>
                @endforeach
            </div>
        </article>
    </section>

    @can('create', \App\Models\CalendarReminder::class)
        <div class="agenda-modal" data-dashboard-agenda-day-modal hidden>
            <div class="agenda-modal-backdrop" data-dashboard-close-day></div>
            <section class="agenda-modal-card agenda-day-modal-card" aria-labelledby="dashboard-agenda-day-title">
                <header>
                    <div><p class="eyebrow">Agenda del día</p><h2 id="dashboard-agenda-day-title" data-dashboard-day-title>Recordatorios</h2></div>
                    <button class="agenda-modal-close" type="button" data-dashboard-close-day aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
                </header>
                <div class="agenda-day-modal-list" data-dashboard-day-list></div>
                <footer>
                    <button class="button button-secondary" type="button" data-dashboard-close-day>Cerrar</button>
                    <button class="button button-primary" type="button" data-dashboard-new-from-day><i class="bi bi-plus-lg"></i> Nuevo recordatorio</button>
                </footer>
            </section>
        </div>
        @foreach($calendarReminders as $date => $dayItems)
            <template data-dashboard-day-template="{{ $date }}">
                @foreach($dayItems as $item)
                    <a class="agenda-day-modal-item {{ $item->is_done ? 'is-done' : '' }}" style="--event-color: {{ $item->color ?: '#E31B23' }}" href="{{ route('calendar.edit', $item) }}">
                        <span class="agenda-day-modal-status">{{ $item->is_done ? 'Hecho' : 'Pendiente' }}</span>
                        <span><strong>{{ $item->title }}</strong><small>{{ $item->description ?: 'Sin descripción' }}</small></span>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                @endforeach
            </template>
        @endforeach
        <div class="agenda-modal" data-dashboard-agenda-modal hidden>
            <div class="agenda-modal-backdrop" data-dashboard-close-modal></div>
            <form class="agenda-modal-card" method="POST" action="{{ route('calendar.store') }}">
                @csrf
                <header>
                    <div><p class="eyebrow">Agenda global</p><h2>Nuevo recordatorio</h2></div>
                    <button class="agenda-modal-close" type="button" data-dashboard-close-modal aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
                </header>
                <div class="agenda-modal-fields">
                    <label><span>Fecha</span><input type="date" name="event_date" data-dashboard-agenda-date required></label>
                    <label><span>Título</span><input type="text" name="title" maxlength="255" placeholder="Ej. Revisión de inventario" required></label>
                    <label class="agenda-modal-color-field"><span>Color</span><span class="agenda-modal-color-control"><input type="color" name="color" value="#E31B23" aria-label="Elegir color del evento"></span></label>
                </div>
                <label class="textarea-field"><span>Descripción</span><textarea name="description" placeholder="Agrega contexto o instrucciones"></textarea></label>
                <label class="textarea-field"><span>Comentarios</span><textarea name="comments" placeholder="Notas adicionales (opcional)"></textarea></label>
                <footer><button class="button button-secondary" type="button" data-dashboard-close-modal>Cancelar</button><button class="button button-primary" type="submit"><i class="bi bi-check2"></i> Guardar evento</button></footer>
            </form>
        </div>
    @endcan
@endsection

@push('scripts')
<script>
(() => {
    const createModal = document.querySelector('[data-dashboard-agenda-modal]');
    const dayModal = document.querySelector('[data-dashboard-agenda-day-modal]');
    if (!createModal || !dayModal) return;
    const dateInput = createModal.querySelector('[data-dashboard-agenda-date]');
    const titleInput = createModal.querySelector('input[name="title"]');
    const dayTitle = dayModal.querySelector('[data-dashboard-day-title]');
    const dayList = dayModal.querySelector('[data-dashboard-day-list]');
    let selectedDate = '';
    const closeAll = () => { createModal.hidden = true; dayModal.hidden = true; document.body.classList.remove('modal-open'); };
    const openCreate = (date) => {
        selectedDate = date || selectedDate;
        if (selectedDate) dateInput.value = selectedDate;
        dayModal.hidden = true; createModal.hidden = false; document.body.classList.add('modal-open');
        window.setTimeout(() => titleInput.focus(), 40);
    };
    const openDay = (date) => {
        const template = document.querySelector('[data-dashboard-day-template="' + date + '"]');
        if (!template) return openCreate(date);
        selectedDate = date; dayList.replaceChildren(template.content.cloneNode(true));
        dayTitle.textContent = new Intl.DateTimeFormat('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(date + 'T12:00:00'));
        createModal.hidden = true; dayModal.hidden = false; document.body.classList.add('modal-open');
    };
    document.querySelectorAll('[data-dashboard-open-agenda-modal]').forEach((button) => button.addEventListener('click', () => openCreate(button.dataset.date)));
    document.querySelectorAll('[data-dashboard-open-agenda-day]').forEach((button) => button.addEventListener('click', () => openDay(button.dataset.date)));
    document.querySelectorAll('[data-dashboard-close-modal], [data-dashboard-close-day]').forEach((button) => button.addEventListener('click', closeAll));
    dayModal.querySelector('[data-dashboard-new-from-day]').addEventListener('click', () => openCreate(selectedDate));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && (!createModal.hidden || !dayModal.hidden)) closeAll(); });
})();
</script>
@endpush
