@extends('layouts.app')
@section('title', 'Editar tinta')
@section('page-title', 'Editar tinta')
@section('content')<a class="back-link" href="{{ route('inks.show', $ink) }}">&larr; Volver al detalle</a>@include('supplies.inks._form')@endsection
