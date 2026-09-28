@extends('layouts.app')
@section('title', $fileCatalog->alias_name ?: $fileCatalog->original_name)
@section('page-title', 'Detalle de archivo')
@section('content')
<div class="detail-toolbar">
    <a class="back-link" href="{{ route('files.index') }}">&larr; Volver a archivos</a>
    <div class="page-actions">
        <button type="button" class="button button-secondary" onclick="copyFileDownloadLink('{{ route('files.download', $fileCatalog) }}', this)"><i class="bi bi-link-45deg" aria-hidden="true"></i> Copiar enlace</button>
        <a class="button button-primary" href="{{ route('files.download', $fileCatalog) }}"><i class="bi bi-download" aria-hidden="true"></i> Descargar</a>
        @can('update', $fileCatalog)<a class="button button-secondary" href="{{ route('files.edit', $fileCatalog) }}">Editar</a>@endcan
    </div>
</div>
<section class="asset-header">
    <div class="asset-icon"><i class="bi {{ $fileCatalog->iconClass() }}" aria-hidden="true"></i></div>
    <div class="asset-heading">
        <p class="eyebrow">Repositorio privado &middot; {{ strtoupper($fileCatalog->extension() ?: 'ARCHIVO') }}</p>
        <h2>{{ $fileCatalog->alias_name ?: $fileCatalog->original_name }}</h2>
        <p>{{ $fileCatalog->alias_name && $fileCatalog->alias_name !== $fileCatalog->original_name ? $fileCatalog->original_name.' · ' : '' }}{{ $fileCatalog->formattedSize() }}</p>
    </div>
</section>

@if($fileCatalog->canPreview())
<section class="panel" style="margin-top:18px;">
    <div class="panel-header">
        <div><p class="eyebrow">Visualización</p><h2>Vista previa integrada</h2></div>
        <a class="button button-secondary" href="{{ route('files.preview', $fileCatalog) }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Abrir en pestaña</a>
    </div>
    <div style="border-radius:10px;overflow:hidden;background:#0c1119;display:flex;align-items:center;justify-content:center;min-height:280px;padding:12px;">
        @if($fileCatalog->typeGroup() === 'image')
            <img src="{{ route('files.preview', $fileCatalog) }}" alt="{{ $fileCatalog->original_name }}" style="max-width:100%;max-height:600px;border-radius:8px;object-fit:contain;">
        @elseif($fileCatalog->typeGroup() === 'pdf')
            <iframe src="{{ route('files.preview', $fileCatalog) }}" style="width:100%;height:680px;border:0;border-radius:8px;background:#333;"></iframe>
        @elseif($fileCatalog->typeGroup() === 'audio')
            <audio controls src="{{ route('files.preview', $fileCatalog) }}" style="width:min(500px, 100%);"></audio>
        @elseif($fileCatalog->typeGroup() === 'video')
            <video controls src="{{ route('files.preview', $fileCatalog) }}" style="max-width:100%;max-height:560px;border-radius:8px;"></video>
        @else
            <iframe src="{{ route('files.preview', $fileCatalog) }}" style="width:100%;height:450px;border:0;border-radius:8px;background:#1e293b;color:#fff;"></iframe>
        @endif
    </div>
</section>
@endif

<section class="detail-grid">
    <article><span>Nombre descriptivo</span><strong>{{ $fileCatalog->alias_name ?: 'Sin nombre descriptivo' }}</strong></article>
    <article><span>Nombre original</span><strong>{{ $fileCatalog->original_name }}</strong></article>
    <article><span>Tipo de archivo</span><strong>{{ strtoupper(pathinfo($fileCatalog->original_name, PATHINFO_EXTENSION)) ?: 'Sin extension' }}</strong></article>
    <article><span>Tamano</span><strong>{{ $fileCatalog->formattedSize() }}</strong></article>
    <article><span>Subido por</span><strong>{{ $fileCatalog->uploader?->display_name ?: 'Importado' }}</strong></article>
    <article><span>Fecha de carga</span><strong>{{ $fileCatalog->upload_date?->format('d/m/Y H:i') ?: '-' }}</strong></article>
</section>
@if($fileCatalog->comments)<section class="panel"><div class="panel-header"><div><p class="eyebrow">Notas</p><h2>Comentarios</h2></div></div><p style="white-space:pre-wrap;">{{ $fileCatalog->comments }}</p></section>@endif
@include('shared.audit')

@push('scripts')
<script>
function copyFileDownloadLink(url, btn) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2" style="color:#10b981;"></i> Copiado';
            setTimeout(() => { btn.innerHTML = original; }, 1800);
        });
    } else {
        prompt('Copia el enlace:', url);
    }
}
</script>
@endpush
@endsection

