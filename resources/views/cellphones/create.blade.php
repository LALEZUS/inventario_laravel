@extends('layouts.app')
@section('title','Nuevo celular') @section('page-title','Nuevo celular')
@section('content')<a class="back-link" href="{{ route('cellphones.index') }}">&larr; Volver a celulares</a>@include('cellphones._form')@endsection
