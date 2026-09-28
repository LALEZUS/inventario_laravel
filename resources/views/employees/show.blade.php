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
<section class="asset-header">
    <div class="asset-icon">{{ collect(explode(' ', $employee->full_name))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->join('') }}</div>
    <div class="asset-heading"><p class="eyebrow">Employee profile</p><h2>{{ $employee->full_name }}</h2><p>{{ $employee->position ?: 'Sin puesto' }} &middot; {{ $employee->department ?: 'Sin departamento' }}</p></div>
    <span class="status large {{ $employee->status === 'Inactivo' ? 'status-muted' : '' }}">{{ $employee->status }}</span>
</section>
<section class="detail-grid">
    @foreach([
        'Departamento' => $employee->department,
        'Puesto' => $employee->position,
        'Correo corporativo' => $employee->email_corporate,
        'Extension' => $employee->extension,
        'Computadoras' => $employee->hardwareAssets->count(),
        'Celulares' => $employee->cellphones->count(),
        'Perifericos' => $employee->peripherals->count(),
        'Impresoras' => $employee->printers->count(),
        'Correos Outlook' => $employee->outlookAccounts->count(),
        'Actualizado' => $employee->updated_at?->format('d/m/Y H:i'),
    ] as $label => $value)<article><span>{{ $label }}</span><strong>{{ filled($value) ? $value : '-' }}</strong></article>@endforeach
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
