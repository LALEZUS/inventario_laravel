@extends('layouts.app')
@section('title', 'Nueva impresora')
@section('page-title', 'Nueva impresora')
@section('content')
<a class="back-link" href="{{ route('printers.index') }}">&larr; Volver a impresoras</a>
@include('printers._form')
@endsection
