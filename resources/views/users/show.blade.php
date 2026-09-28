@extends('layouts.app')
@section('title', $managedUser->display_name)
@section('page-title', 'Detalle del usuario')
@section('content')
@php
    $roleLabel = ['admin'=>'Administrador','soporte'=>'Soporte','consulta'=>'Consulta'][$managedUser->role] ?? ucfirst($managedUser->role);
    $initials = collect(explode(' ', $managedUser->display_name))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->join('');
    $capabilities = match($managedUser->role) {
        'admin' => ['Administrar usuarios', 'Crear y editar registros', 'Eliminar registros', 'Consultar auditoria y respaldos'],
        'soporte' => ['Crear registros', 'Editar registros', 'Consultar inventario', 'Gestionar archivos operativos'],
        default => ['Consultar inventario', 'Abrir detalles', 'Usar busqueda global', 'Sin permisos de modificacion'],
    };
@endphp
<div class="detail-toolbar">
    <a class="back-link" href="{{ route('users.index') }}">&larr; Volver a usuarios</a>
    <div class="page-actions">
        <a class="button button-primary" href="{{ route('users.edit', $managedUser) }}">Editar usuario</a>
        @can('delete', $managedUser)<form method="POST" action="{{ route('users.destroy', $managedUser) }}" onsubmit="return confirm('Eliminar este usuario? Perdera inmediatamente el acceso al inventario.');">@csrf @method('DELETE')<button class="button button-danger">Eliminar</button></form>@endcan
    </div>
</div>
<section class="asset-header user-profile-header">
    <div class="asset-icon">{{ $initials }}</div>
    <div class="asset-heading"><p class="eyebrow">Perfil de acceso</p><h2>{{ $managedUser->display_name }}</h2><p>{{ '@'.$managedUser->username }} @if(auth()->id()===$managedUser->id)&middot; Esta es tu cuenta @endif</p></div>
    <span class="role-badge role-{{ $managedUser->role }} large">{{ $roleLabel }}</span>
</section>
<section class="detail-grid">
    <article><span>Nombre completo</span><strong>{{ $managedUser->display_name }}</strong></article>
    <article><span>Usuario</span><strong>{{ '@'.$managedUser->username }}</strong></article>
    <article><span>Rol</span><strong>{{ $roleLabel }}</strong></article>
    <article><span>Cuenta creada</span><strong>{{ $managedUser->created_at?->format('d/m/Y H:i') ?: '-' }}</strong></article>
    <article><span>Ultima actualizacion</span><strong>{{ $managedUser->updated_at?->format('d/m/Y H:i') ?: '-' }}</strong></article>
    <article><span>Contrasena</span><strong>Protegida y cifrada</strong></article>
</section>
<section class="panel access-panel">
    <div class="panel-header"><div><p class="eyebrow">Alcance del rol</p><h2>Permisos principales</h2></div><i class="bi bi-shield-check" aria-hidden="true"></i></div>
    <div class="capability-grid">@foreach($capabilities as $capability)<article><i class="bi bi-check2-circle" aria-hidden="true"></i><span>{{ $capability }}</span></article>@endforeach</div>
    @if($managedUser->comments)<div class="user-comments"><strong>Comentarios administrativos</strong><p>{{ $managedUser->comments }}</p></div>@endif
</section>
@include('shared.audit')
@endsection
