@extends('layouts.app')
@section('title','Exportar '.$dataset['title']) @section('page-title','Exportar a Excel')
@section('content')
<div class="detail-toolbar"><a class="back-link" href="{{url()->previous()}}">&larr; Volver</a></div>
<form class="panel export-panel" method="POST" action="{{route('inventory-export.download',$dataset['key'])}}">
    @csrf
    <div class="panel-header"><div><p class="eyebrow">Archivo de Excel</p><h2>{{$dataset['title']}}</h2><p>Selecciona las columnas que deseas incluir. @if(array_key_exists('password', $dataset['columns'])) La contraseña es opcional y el archivo debe manejarse como información confidencial. @else Los campos sensibles se excluyen siempre. @endif</p></div><button class="button button-secondary" type="button" data-toggle-checks>Seleccionar todas</button></div>
    @error('columns')<div class="flash flash-error">{{$message}}</div>@enderror
    <div class="export-columns">
        @foreach($dataset['columns'] as $column=>$label)<label><input type="checkbox" name="columns[]" value="{{$column}}" @checked($column !== 'password')><span>{{$label}}</span></label>@endforeach
    </div>
    <footer class="form-footer"><a class="button button-secondary" href="{{url()->previous()}}">Cancelar</a><button class="button button-primary"><i class="bi bi-file-earmark-excel"></i> Exportar ahora</button></footer>
</form>
@endsection
@push('scripts')<script>document.querySelector('[data-toggle-checks]')?.addEventListener('click',e=>{const boxes=[...document.querySelectorAll('.export-columns input')],check=boxes.some(x=>!x.checked);boxes.forEach(x=>x.checked=check);e.currentTarget.textContent=check?'Quitar seleccion':'Seleccionar todas';});</script>@endpush
