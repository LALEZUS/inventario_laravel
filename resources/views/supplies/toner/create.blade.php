@extends('layouts.app')
@section('title', 'Nuevo toner')
@section('page-title', 'Nuevo toner')
@section('content')<a class="back-link" href="{{ route('supplies.index', ['type' => 'toner']) }}">&larr; Volver a consumibles</a>@include('supplies.toner._form')@endsection
