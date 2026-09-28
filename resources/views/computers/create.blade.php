@extends('layouts.app')

@section('title', 'Nueva computadora')
@section('page-title', 'Nueva computadora')

@section('content')
    @include('computers._form', ['submitLabel' => 'Guardar computadora'])
@endsection
