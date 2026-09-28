@extends('layouts.app')

@section('title', $enterpriseNetwork->network_name)
@section('page-title', 'Detalle de red empresarial')

@section('content')
    <div class="detail-toolbar">
        <a class="back-link" href="{{ route('network.index', ['type' => 'networks']) }}">&larr; Volver a redes</a>
        <div class="page-actions">
            @can('update', $enterpriseNetwork)
                <a class="button button-primary" href="{{ route('enterprise-networks.edit', $enterpriseNetwork) }}">Editar</a>
            @endcan
            @can('delete', $enterpriseNetwork)
                <form method="POST" action="{{ route('enterprise-networks.destroy', $enterpriseNetwork) }}" onsubmit="return confirm('Eliminar esta red?');">
                    @csrf
                    @method('DELETE')
                    <button class="button button-danger">Eliminar</button>
                </form>
            @endcan
        </div>
    </div>

    <section class="asset-header">
        <div class="asset-icon">WIFI</div>
        <div class="asset-heading">
            <p class="eyebrow">Enterprise network</p>
            <h2>{{ $enterpriseNetwork->network_name }}</h2>
            <p>{{ $enterpriseNetwork->location ?: 'Sin ubicacion' }} &middot; {{ $enterpriseNetwork->encryption ?: 'Cifrado no registrado' }}</p>
        </div>
        <span class="security-label">Red protegida</span>
    </section>

    <section class="detail-grid">
        @foreach([
            'VLAN' => $enterpriseNetwork->vlan,
            'Ubicacion' => $enterpriseNetwork->location,
            'Cifrado' => $enterpriseNetwork->encryption,
            'Creada' => $enterpriseNetwork->created_at?->format('d/m/Y H:i'),
            'Actualizada' => $enterpriseNetwork->updated_at?->format('d/m/Y H:i'),
        ] as $label => $value)
            <article>
                <span>{{ $label }}</span>
                <strong>{{ $value ?: '-' }}</strong>
            </article>
        @endforeach

        @can('viewSensitive', $enterpriseNetwork)
            <article class="sensitive-detail">
                <span>Contrase&ntilde;a de la red</span>
                <strong class="secret-value" data-secret-url="{{ route('enterprise-networks.secrets', $enterpriseNetwork) }}" data-secret-field="password">{{ $enterpriseNetwork->password ? '********' : '-' }}</strong>
                @if($enterpriseNetwork->password)
                    <button class="reveal-secret" type="button"><i class="bi bi-eye"></i> Mostrar</button>
                @endif
            </article>
        @endcan
    </section>

    @if($enterpriseNetwork->comments || $enterpriseNetwork->notes_extra)
        <section class="panel">
            <div class="panel-header">
                <div>
                    <p class="eyebrow">Documentacion</p>
                    <h2>Descripcion y notas</h2>
                </div>
            </div>
            @if($enterpriseNetwork->comments)
                <p>{{ $enterpriseNetwork->comments }}</p>
            @endif
            @if($enterpriseNetwork->notes_extra)
                <p>{{ $enterpriseNetwork->notes_extra }}</p>
            @endif
        </section>
    @endif

    @include('shared.audit')
    @include('credentials._secret-script')
@endsection
