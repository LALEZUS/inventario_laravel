@extends('layouts.app')
@section('title', 'Nueva tinta')
@section('page-title', 'Nueva tinta')
@section('content')<a class="back-link" href="{{ route('supplies.index', ['type' => 'inks']) }}">&larr; Volver a consumibles</a>@include('supplies.inks._form')@endsection
