@extends('layouts.app')
@section('title', 'Editar '.$printer->name)
@section('page-title', 'Editar impresora')
@section('content')
<a class="back-link" href="{{ route('printers.show', $printer) }}">&larr; Volver al detalle</a>
@include('printers._form')
@endsection
