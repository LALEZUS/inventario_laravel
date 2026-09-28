@extends('layouts.app')
@section('title','Editar celular') @section('page-title','Editar celular')
@section('content')<a class="back-link" href="{{ route('cellphones.show',$cellphone) }}">&larr; Volver al detalle</a>@include('cellphones._form')@endsection
