@extends('layouts.app')
@section('title', 'Editar usuario')
@section('page-title', 'Editar usuario')
@section('content')
<a class="back-link" href="{{ route('users.show', $managedUser) }}">&larr; Volver al detalle</a>
@include('users._form')
@endsection
