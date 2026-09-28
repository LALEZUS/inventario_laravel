@extends('layouts.app')
@section('title', 'Nuevo usuario')
@section('page-title', 'Nuevo usuario')
@section('content')
<a class="back-link" href="{{ route('users.index') }}">&larr; Volver a usuarios</a>
@include('users._form')
@endsection
