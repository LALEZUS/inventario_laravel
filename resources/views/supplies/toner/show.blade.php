@extends('layouts.app')
@section('title', $toner->brand.' '.$toner->model)
@section('page-title', 'Detalle de toner')
@section('content')
<div class="detail-toolbar"><a class="back-link" href="{{ route('supplies.index', ['type' => 'toner']) }}">&larr; Volver a toner</a><div class="page-actions">@can('update', $toner)<a class="button button-primary" href="{{ route('toner.edit', $toner) }}">Editar toner</a>@endcan @can('delete', $toner)<form method="POST" action="{{ route('toner.destroy', $toner) }}" onsubmit="return confirm('Eliminar este toner? Se desvinculara de sus impresoras.');">@csrf @method('DELETE')<button class="button button-danger">Eliminar</button></form>@endcan</div></div>
<section class="asset-header"><div class="asset-icon">TON</div><div class="asset-heading"><p class="eyebrow">Toner supply</p><h2>{{ $toner->brand }}</h2><p>{{ $toner->model }}</p></div><span class="status large">{{ $toner->status }}</span></section>
<section class="detail-grid">@foreach(['Existencia'=>$toner->quantity.' unidades','Umbral de alerta'=>$toner->low_stock_threshold.' unidades o menos','Creado'=>$toner->created_at?->format('d/m/Y H:i'),'Actualizado'=>$toner->updated_at?->format('d/m/Y H:i')] as $label=>$value)<article><span>{{ $label }}</span><strong>{{ $value ?: '-' }}</strong></article>@endforeach</section>
@include('supplies.printers', ['printers' => $printers])
@if($toner->notes)<section class="panel"><div class="panel-header"><div><p class="eyebrow">Seguimiento</p><h2>Comentarios</h2></div></div><p>{{ $toner->notes }}</p></section>@endif
@include('shared.audit')
@endsection
