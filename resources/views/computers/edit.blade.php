@extends('layouts.app')

@section('title', 'Editar '.$computer->name)
@section('page-title', 'Editar computadora')

@section('content')
    @include('computers._form', ['submitLabel' => 'Guardar cambios'])
@endsection
