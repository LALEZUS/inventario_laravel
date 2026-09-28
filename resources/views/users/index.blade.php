@extends('layouts.app')
@section('title', 'Usuarios')
@section('page-title', 'Gestion de usuarios')
@section('content')
<section class="page-intro compact users-intro">
    <div>
        <p class="eyebrow">Acceso y seguridad</p>
        <h2>{{ $users->total() }} usuarios encontrados</h2>
        <p>Administra quienes pueden ingresar y que operaciones pueden realizar.</p>
    </div>
    <div class="page-actions">
        <form method="GET" class="search-form">
            <label class="sr-only" for="search">Buscar usuarios</label>
            <input id="search" name="search" value="{{ $search }}" placeholder="Nombre, usuario o rol...">
            <button class="button button-secondary">Buscar</button>
            @if($search !== '')<a class="button button-secondary" href="{{ route('users.index') }}">Limpiar</a>@endif
        </form>
        <x-recent-filter />
        <a class="button button-primary" href="{{ route('users.create') }}"><i class="bi bi-person-plus" aria-hidden="true"></i> Nuevo usuario</a>
    </div>
</section>

<section class="user-kpis" aria-label="Resumen de usuarios">
    <article><i class="bi bi-people" aria-hidden="true"></i><div><span>Total</span><strong>{{ $roleCounts->sum() }}</strong></div></article>
    <article><i class="bi bi-shield-lock" aria-hidden="true"></i><div><span>Administradores</span><strong>{{ $roleCounts['admin'] ?? 0 }}</strong></div></article>
    <article><i class="bi bi-tools" aria-hidden="true"></i><div><span>Soporte</span><strong>{{ $roleCounts['soporte'] ?? 0 }}</strong></div></article>
    <article><i class="bi bi-eye" aria-hidden="true"></i><div><span>Consulta</span><strong>{{ $roleCounts['consulta'] ?? 0 }}</strong></div></article>
</section>

<section class="panel table-panel">
    <div class="table-scroll"><table><thead><tr><th>Usuario</th><th>Rol</th><th>Creado</th><th>Ultima actualizacion</th><th>Acciones</th></tr></thead><tbody>
    @forelse($users as $managedUser)
        @php($initials = collect(explode(' ', $managedUser->display_name))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->join(''))
        <tr>
            <td><div class="user-cell"><span class="user-avatar">{{ $initials }}</span><div><strong>{{ $managedUser->display_name }}</strong><small>{{ '@'.$managedUser->username }} @if(auth()->id() === $managedUser->id) &middot; Tu cuenta @endif</small></div></div></td>
            <td><span class="role-badge role-{{ $managedUser->role }}">{{ ['admin'=>'Administrador','soporte'=>'Soporte','consulta'=>'Consulta'][$managedUser->role] ?? ucfirst($managedUser->role) }}</span></td>
            <td>{{ $managedUser->created_at?->format('d/m/Y') ?: '-' }}</td>
            <td>{{ $managedUser->updated_at?->format('d/m/Y H:i') ?: '-' }}</td>
            <td><div class="row-actions">
                <a class="table-action" href="{{ route('users.show', $managedUser) }}">Detalles</a>
                <a class="table-action secondary" href="{{ route('users.edit', $managedUser) }}">Editar</a>
                @can('delete', $managedUser)<form method="POST" action="{{ route('users.destroy', $managedUser) }}" onsubmit="return confirm('Eliminar este usuario? Perdera inmediatamente el acceso al inventario.');">@csrf @method('DELETE')<button type="submit">Eliminar</button></form>@endcan
            </div></td>
        </tr>
    @empty<tr><td colspan="5" class="empty">No hay usuarios para esta busqueda.</td></tr>@endforelse
    </tbody></table></div>
    @if($users->hasPages())<nav class="pagination">@if($users->onFirstPage())<span>Anterior</span>@else<a href="{{ $users->previousPageUrl() }}">Anterior</a>@endif<strong>Pagina {{ $users->currentPage() }} de {{ $users->lastPage() }}</strong>@if($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}">Siguiente</a>@else<span>Siguiente</span>@endif</nav>@endif
</section>
@endsection
