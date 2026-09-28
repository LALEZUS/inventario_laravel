@extends('layouts.app')
@section('title', 'Editar toner')
@section('page-title', 'Editar toner')
@section('content')<a class="back-link" href="{{ route('toner.show', $toner) }}">&larr; Volver al detalle</a>@include('supplies.toner._form')@endsection
