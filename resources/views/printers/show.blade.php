@extends('layouts.app')
@section('title', $printer->name)
@section('page-title', 'Detalle de impresora')
@section('content')
<div class="detail-toolbar">
    <a class="back-link" href="{{ route('printers.index') }}">&larr; Volver a impresoras</a>
    <div class="page-actions">
        @can('update', $printer)<a class="button button-primary" href="{{ route('printers.edit', $printer) }}">Editar impresora</a>@endcan
        @can('delete', $printer)<form method="POST" action="{{ route('printers.destroy', $printer) }}" onsubmit="return confirm('Eliminar esta impresora?');">@csrf @method('DELETE')<button class="button button-danger">Eliminar</button></form>@endcan
    </div>
</div>

<section class="asset-header">
    <div class="asset-icon">IMP</div>
    <div class="asset-heading"><p class="eyebrow">Printer asset</p><h2>{{ $printer->name }}</h2><p>{{ $printer->brand ?: 'Sin marca' }} &middot; {{ $printer->model ?: 'Sin modelo' }}</p></div>
    <span class="status large">{{ $printer->status }}</span>
</section>

<section class="detail-grid">
    @foreach([
        'Folio' => $printer->code,
        'Serie' => $printer->serial,
        'Conexion' => $printer->is_network ? 'Impresora en red' : 'Conexion local',
        'Direccion IP' => $printer->ip_address,
        'Zona' => $printer->zone,
        'Asignada a' => $printer->assigned_name,
        'Tipo de suministro' => $printer->supply_type,
        'Referencia' => $printer->ink_type,
        'Actualizada' => $printer->updated_at?->format('d/m/Y H:i'),
    ] as $label => $value)<article><span>{{ $label }}</span><strong>{{ $value ?: '-' }}</strong></article>@endforeach
</section>

<section class="panel">
    <div class="panel-header"><div><p class="eyebrow">Existencias vinculadas</p><h2>Consumibles compatibles</h2></div><strong>{{ $inks->count() + $toners->count() }}</strong></div>
    <div class="supply-grid">
        @foreach($inks as $ink)<a href="{{ route('inks.show', $ink) }}"><article class="ink-supply-card ink-color-{{ $ink->colorKey() }}"><span class="supply-kind ink-kind"><i aria-hidden="true"></i>Tinta</span><div><strong>{{ $ink->brand }} {{ $ink->type }}</strong><small><x-ink-color :ink="$ink" compact /> &middot; {{ $ink->capacity ?: 'Capacidad no registrada' }}</small></div><b>{{ $ink->quantity }} disponibles</b></article></a>@endforeach
        @foreach($toners as $toner)<a href="{{ route('toner.show', $toner) }}"><article><span class="supply-kind toner">Toner</span><div><strong>{{ $toner->brand }}</strong><small>{{ $toner->model ?: 'Sin modelo' }} &middot; {{ $toner->status }}</small></div><b>{{ $toner->quantity }} disponibles</b></article></a>@endforeach
        @if($inks->isEmpty() && $toners->isEmpty())<p class="empty">No hay consumibles vinculados a esta impresora.</p>@endif
    </div>
</section>

@if($printer->comments)<section class="panel"><div class="panel-header"><div><p class="eyebrow">Seguimiento</p><h2>Comentarios</h2></div></div><p>{{ $printer->comments }}</p></section>@endif
@include('shared.files', ['asset' => $printer, 'assetType' => 'printer'])
@include('shared.audit')
@endsection
