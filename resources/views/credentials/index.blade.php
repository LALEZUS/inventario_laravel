@extends('layouts.app')
@section('title', 'Credenciales')
@section('page-title', 'Control de credenciales')
@section('content')
<section class="page-intro compact">
    <div><p class="eyebrow">Accesos y licenciamiento</p><h2>Credenciales empresariales</h2><p>Cuentas, configuraciones Outlook y licencias bajo permisos controlados.</p></div>
    <div class="page-actions">
        <x-recent-filter />
        @if($type === 'accounts') @can('create', \App\Models\AccountCredential::class)<a class="button button-primary" href="{{ route('account-credentials.create') }}">Nueva cuenta</a>@endcan
        @elseif($type === 'outlook') @can('create', \App\Models\OutlookAccount::class)<a class="button button-primary" href="{{ route('outlook-accounts.create') }}">Nuevo correo</a>@endcan
        @else @can('create', \App\Models\SoftwareLicense::class)<a class="button button-primary" href="{{ route('software-licenses.create') }}">Nueva licencia</a>@endcan @endif
    </div>
</section>
<section class="stats-grid credential-stats">
    <article><span>Cuentas</span><strong>{{ $stats['accounts'] }}</strong><small>Accesos corporativos</small></article>
    <article><span>Outlook</span><strong>{{ $stats['outlook'] }}</strong><small>Configuraciones externas</small></article>
    <article><span>Licencias</span><strong>{{ $stats['licenses'] }}</strong><small>Productos registrados</small></article>
    <article><span>Por vencer</span><strong>{{ $stats['expiring'] }}</strong><small>En los siguientes 30 dias</small></article>
</section>
<section class="panel table-panel">
    <x-module-tabs :active="$type" label="Tipo de credencial" :tabs="[
        'accounts' => ['label'=>'Cuentas', 'description'=>'Accesos y servicios corporativos', 'count'=>$stats['accounts'], 'icon'=>'bi-person-lock', 'url'=>route('credentials.index',['type'=>'accounts'])],
        'outlook' => ['label'=>'Outlook', 'description'=>'Correo externo y servidores', 'count'=>$stats['outlook'], 'icon'=>'bi-envelope-at', 'url'=>route('credentials.index',['type'=>'outlook'])],
        'licenses' => ['label'=>'Licencias', 'description'=>'Claves, productos y vencimientos', 'count'=>$stats['licenses'], 'icon'=>'bi-shield-check', 'url'=>route('credentials.index',['type'=>'licenses'])],
    ]" />
    <form class="table-filters credential-filters" method="GET">
        <input type="hidden" name="type" value="{{ $type }}">
        <label><span>Buscar</span><input name="search" value="{{ $search }}" placeholder="Cuenta, servicio, responsable o proveedor..."></label>
        <label><span>Estado</span><select name="status"><option value="">Todos</option>@foreach($statusOptions as $option)<option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>@endforeach</select></label>
        <button class="button button-secondary">Aplicar</button>
        @if($search !== '' || $status !== '')<a class="button button-secondary" href="{{ route('credentials.index',['type'=>$type]) }}">Limpiar</a>@endif
    </form>
    <div class="table-scroll"><table>
        @if($type === 'accounts')
        <thead><tr><th>Cuenta</th><th>Tipo</th><th>Asignada a</th><th>Estado</th><th>Actualizada</th><th></th></tr></thead>
        <tbody>@forelse($items as $item)<tr><td><strong>{{ $item->email }}</strong></td><td>{{ $item->account_type }}</td><td>{{ $item->employee?->full_name ?: ($item->assigned_to ?: '-') }}</td><td><span class="status">{{ $item->status }}</span></td><td>{{ $item->updated_at?->format('d/m/Y H:i') }}</td><td><div class="row-actions"><a class="table-action" href="{{ route('account-credentials.show',$item) }}">Detalles</a>@can('update',$item)<a class="table-action secondary" href="{{ route('account-credentials.edit',$item) }}">Editar</a>@endcan</div></td></tr>@empty<tr><td colspan="6" class="empty">No hay cuentas para estos filtros.</td></tr>@endforelse</tbody>
        @elseif($type === 'outlook')
        <thead><tr><th>Correo</th><th>Empleado</th><th>Entrada</th><th>Salida</th><th>Estado</th><th>Actualizada</th><th></th></tr></thead>
        <tbody>@forelse($items as $item)<tr><td><strong>{{ $item->correo }}</strong></td><td>{{ $item->employee?->full_name ?: '-' }}</td><td>{{ $item->servidor_entrada ?: '-' }}</td><td>{{ $item->servidor_salida ?: '-' }}</td><td><span class="status">{{ $item->estatus }}</span></td><td>{{ $item->updated_at?->format('d/m/Y H:i') }}</td><td><div class="row-actions"><a class="table-action" href="{{ route('outlook-accounts.show',$item) }}">Detalles</a>@can('update',$item)<a class="table-action secondary" href="{{ route('outlook-accounts.edit',$item) }}">Editar</a>@endcan</div></td></tr>@empty<tr><td colspan="7" class="empty">No hay correos para estos filtros.</td></tr>@endforelse</tbody>
        @else
        <thead><tr><th>Producto / Servicio</th><th>Tipo</th><th>Proveedor</th><th>Vencimiento</th><th>Estado</th><th></th></tr></thead>
        <tbody>@forelse($items as $item)<tr><td><strong>{{ $item->name }}</strong></td><td>{{ $item->type ?: '-' }}</td><td>{{ $item->vendor ?: '-' }}</td><td>{{ $item->expiration_date?->format('d/m/Y') ?: '-' }}</td><td><span class="status">{{ $item->status ?: 'Sin estado' }}</span></td><td><div class="row-actions"><a class="table-action" href="{{ route('software-licenses.show',$item) }}">Detalles</a>@can('update',$item)<a class="table-action secondary" href="{{ route('software-licenses.edit',$item) }}">Editar</a>@endcan</div></td></tr>@empty<tr><td colspan="6" class="empty">No hay licencias para estos filtros.</td></tr>@endforelse</tbody>
        @endif
    </table></div>
    @if($items->hasPages())<nav class="pagination">@if($items->onFirstPage())<span>Anterior</span>@else<a href="{{ $items->previousPageUrl() }}">Anterior</a>@endif<strong>Pagina {{ $items->currentPage() }} de {{ $items->lastPage() }}</strong>@if($items->hasMorePages())<a href="{{ $items->nextPageUrl() }}">Siguiente</a>@else<span>Siguiente</span>@endif</nav>@endif
</section>
@endsection
