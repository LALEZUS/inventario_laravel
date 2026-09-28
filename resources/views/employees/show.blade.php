@extends('layouts.app')
@section('title', $employee->full_name)
@section('page-title', 'Perfil del empleado')
@section('content')
<div class="detail-toolbar">
    <a class="back-link" href="{{ route('employees.index') }}">&larr; Volver a empleados</a>
    <div class="page-actions">
        @can('update', $employee)<a class="button button-primary" href="{{ route('employees.edit', $employee) }}">Editar empleado</a>@endcan
        @can('delete', $employee)<form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Eliminar este empleado? Los activos conservaran su nombre como referencia historica.');">@csrf @method('DELETE')<button class="button button-danger">Eliminar</button></form>@endcan
    </div>
</div>
<section class="employee-profile-hero">
    <div class="asset-icon">{{ collect(explode(' ', $employee->full_name))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->join('') }}</div>
    <div class="asset-heading"><p class="eyebrow">Perfil del empleado</p><h2>{{ $employee->full_name }}</h2><p>{{ $employee->position ?: 'Sin puesto' }} <span aria-hidden="true">&middot;</span> {{ $employee->department ?: 'Sin departamento' }}</p></div>
    <div class="employee-hero-meta"><span class="status {{ $employee->status === 'Inactivo' ? 'status-muted' : '' }}">{{ $employee->status }}</span><small>Ultima actualizacion · {{ $employee->updated_at?->translatedFormat('d M Y, H:i') }}</small></div>
</section>
@php
    $inventorySummary = [
        ['label' => 'Computadoras', 'count' => $employee->hardwareAssets->count(), 'icon' => 'bi-laptop'],
        ['label' => 'Celulares', 'count' => $employee->cellphones->count(), 'icon' => 'bi-phone'],
        ['label' => 'Perifericos', 'count' => $employee->peripherals->count(), 'icon' => 'bi-mouse'],
        ['label' => 'Impresoras', 'count' => $employee->printers->count(), 'icon' => 'bi-printer'],
        ['label' => 'Correos Outlook', 'count' => $employee->outlookAccounts->count(), 'icon' => 'bi-envelope'],
    ];
@endphp
<section class="employee-overview-grid">
    <div class="employee-primary-column">
        <section class="employee-section employee-contact-section">
            <div class="employee-section-heading"><div><p class="eyebrow">Contacto</p><h2>Como localizarlo</h2></div></div>
            <div class="employee-contact-list">
                <div><i class="bi bi-envelope" aria-hidden="true"></i><span>Correo corporativo</span><strong>{{ $employee->email_corporate ?: 'No registrado' }}</strong></div>
                <div><i class="bi bi-phone" aria-hidden="true"></i><span>Numero de celular</span><strong>{{ $employee->phone_number ?: 'No registrado' }}</strong></div>
                <div><i class="bi bi-telephone" aria-hidden="true"></i><span>Extension</span><strong>{{ $employee->extension ?: 'No registrada' }}</strong></div>
            </div>
</section>
<section class="employee-section employee-inventory-section">
            <div class="employee-section-heading"><div><p class="eyebrow">Inventario asignado</p><h2>Recursos a su cargo</h2></div><strong class="employee-total-assets">{{ collect($inventorySummary)->sum('count') }}</strong></div>
            <div class="employee-inventory-summary">@foreach($inventorySummary as $item)<div class="employee-inventory-item {{ $item['count'] > 0 ? 'has-items' : '' }}"><i class="bi {{ $item['icon'] }}" aria-hidden="true"></i><strong>{{ $item['count'] }}</strong><span>{{ $item['label'] }}</span></div>@endforeach</div>
        </section>
    </div>
    <aside class="employee-qr-panel"><div><p class="eyebrow">Contacto rapido</p><h2>Compartir contacto</h2><p>Escanea para guardar los datos de {{ $employee->full_name }}.</p></div><img src="{{ $employeeQr }}" alt="Codigo QR de contacto de {{ $employee->full_name }}" width="150" height="150"></aside>
</section>
@if($employee->comments)<section class="panel"><div class="panel-header"><div><p class="eyebrow">Seguimiento</p><h2>Comentarios</h2></div></div><p>{{ $employee->comments }}</p></section>@endif
<section class="panel">
    <div class="panel-header"><div><p class="eyebrow">Activos relacionados</p><h2>Equipos y accesos asignados</h2></div><strong>{{ $employee->hardwareAssets->count() + $employee->cellphones->count() + $employee->peripherals->count() + $employee->printers->count() + $employee->outlookAccounts->count() }}</strong></div>
    <div class="asset-relation-grid">
        @foreach($employee->hardwareAssets as $item)<a class="linked-asset" href="{{ route('computers.show', $item) }}"><span>PC</span><div><strong>{{ $item->name }}</strong><small>{{ $item->code ?: 'Sin folio' }} &middot; {{ $item->status }}</small></div><b>Ver</b></a>@endforeach
        @foreach($employee->cellphones as $item)<a class="linked-asset" href="{{ route('cellphones.show', $item) }}"><span>TEL</span><div><strong>{{ $item->model ?: 'Celular' }}</strong><small>{{ $item->phone_number ?: 'Sin linea' }} &middot; {{ $item->status }}</small></div><b>Ver</b></a>@endforeach
        @foreach($employee->peripherals as $item)<a class="linked-asset" href="{{ route('peripherals.show', $item) }}"><span>PER</span><div><strong>{{ $item->name }}</strong><small>{{ $item->code ?: 'Sin folio' }} &middot; {{ $item->status }}</small></div><b>Ver</b></a>@endforeach
        @foreach($employee->printers as $item)<a class="linked-asset" href="{{ route('printers.show', $item) }}"><span>IMP</span><div><strong>{{ $item->name }}</strong><small>{{ $item->ip_address ?: 'Conexion local' }} &middot; {{ $item->status }}</small></div><b>Ver</b></a>@endforeach
        @foreach($employee->outlookAccounts as $item)<a class="linked-asset" href="{{ route('outlook-accounts.show', $item) }}"><span>MAIL</span><div><strong>{{ $item->correo }}</strong><small>Outlook &middot; {{ $item->estatus }}</small></div><b>Ver</b></a>@endforeach
        @if($employee->hardwareAssets->isEmpty() && $employee->cellphones->isEmpty() && $employee->peripherals->isEmpty() && $employee->printers->isEmpty() && $employee->outlookAccounts->isEmpty())<p class="empty">Este empleado no tiene activos ni accesos vinculados.</p>@endif
    </div>
</section>
<section class="panel">
    <div class="panel-header"><div><p class="eyebrow">Trazabilidad</p><h2>Historial de asignaciones</h2></div><strong>{{ $employee->assignments->count() }}</strong></div>
    @if($employee->assignments->isEmpty())
        <p class="empty">Todavia no hay movimientos de asignacion para este empleado.</p>
    @else
        <div class="assignment-timeline">
            @foreach($employee->assignments as $assignment)
                <article class="assignment-event">
                    <span class="assignment-dot" aria-hidden="true"></span>
                    <div><strong>{{ $assignment->computer?->name ?: 'Activo de inventario' }}</strong><p>{{ $assignment->date_assigned?->format('d/m/Y') }} · {{ $assignment->date_returned ? 'Devuelto el '.$assignment->date_returned->format('d/m/Y') : 'Asignacion activa' }}</p>@if($assignment->notes)<small>{{ $assignment->notes }}</small>@endif</div>
                    @if($assignment->computer)<a class="table-action" href="{{ route('computers.show', $assignment->computer) }}">Ver equipo</a>@endif
                </article>
            @endforeach
        </div>
    @endif
</section>
@include('shared.audit')
@endsection
