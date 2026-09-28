@extends('layouts.app')
@section('title', 'Archivos')
@section('page-title', 'Archivos generales')
@section('content')
<section class="page-intro compact">
    <div><p class="eyebrow">Repositorio privado</p><h2>Archivos generales</h2><p>{{ $items->total() }} archivos disponibles.</p></div>
    <div class="page-actions"><x-recent-filter />@can('create', \App\Models\FileCatalog::class)<a class="button button-primary" href="{{ route('files.create') }}"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Subir archivo</a>@endcan</div>
</section>
<section class="panel">
    <form class="search-form" method="GET" action="{{ route('files.index') }}">
        <input name="search" value="{{ $s }}" placeholder="Buscar por nombre, archivo o comentario...">
        @if(!empty($type))<input type="hidden" name="type" value="{{ $type }}">@endif
        <button class="button button-secondary">Buscar</button>
        @if($s || ($type && $type !== 'all'))
            <a class="button button-secondary" href="{{ route('files.index') }}">Limpiar</a>
        @endif
    </form>

    <div class="file-filter-bar">
        @php
            $currentType = $type ?? 'all';
            $filters = [
                'all' => ['label' => 'Todos', 'icon' => 'bi-grid'],
                'installer' => ['label' => 'Instaladores / ISO', 'icon' => 'bi-file-earmark-binary'],
                'archive' => ['label' => 'Comprimidos / ZIP', 'icon' => 'bi-file-earmark-zip'],
                'document' => ['label' => 'Documentos', 'icon' => 'bi-file-earmark-text'],
                'pdf' => ['label' => 'PDF', 'icon' => 'bi-file-earmark-pdf'],
                'image' => ['label' => 'Imágenes', 'icon' => 'bi-file-earmark-image'],
            ];
        @endphp
        @foreach($filters as $key => $f)
            <a href="{{ route('files.index', array_filter(['search' => $s, 'type' => $key !== 'all' ? $key : null])) }}"
               class="file-filter-pill {{ ($currentType === $key || ($key === 'all' && empty($currentType))) ? 'is-active' : '' }}">
                <i class="bi {{ $f['icon'] }}" aria-hidden="true"></i>
                <span>{{ $f['label'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="recent-list file-catalog-list">
        @forelse($items as $item)
            <article class="file-list-row">
                <a class="file-list-main" href="{{ route('files.show', $item) }}">
                    <span class="file-type-icon file-type-{{ $item->typeGroup() }}" aria-label="Archivo {{ $item->extension() ?: 'sin extensión' }}" title=".{{ $item->extension() ?: 'archivo' }}"><i class="bi {{ $item->iconClass() }}" aria-hidden="true"></i></span>
                    <span class="file-list-copy">
                        <strong>{{ $item->alias_name ?: $item->original_name }}</strong>
                        @if($item->alias_name && $item->alias_name !== $item->original_name)<small class="file-original-name">Archivo original: {{ $item->original_name }}</small>@endif
                        <small>{{ $item->formattedSize() }} &middot; {{ $item->uploader?->display_name ?: 'Importado' }} &middot; {{ $item->upload_date?->format('d/m/Y') ?: '' }}</small>
                    </span>
                </a>
                <div class="row-actions file-list-actions">
                    @if($item->canPreview())
                        <button type="button" class="icon-action" onclick="openFilePreview('{{ route('files.preview', $item) }}', '{{ addslashes($item->alias_name ?: $item->original_name) }}', '{{ $item->typeGroup() }}')" aria-label="Vista previa de {{ $item->original_name }}" title="Vista previa"><i class="bi bi-eye" aria-hidden="true"></i></button>
                    @else
                        <a class="icon-action icon-action-detail" href="{{ route('files.show', $item) }}" aria-label="Ver detalles de {{ $item->alias_name ?: $item->original_name }}" title="Ver detalles"><i class="bi bi-info-circle" aria-hidden="true"></i></a>
                    @endif
                    <button type="button" class="icon-action" onclick="copyFileDownloadLink('{{ route('files.download', $item) }}', this)" aria-label="Copiar enlace de descarga" title="Copiar enlace de descarga"><i class="bi bi-link-45deg" aria-hidden="true"></i></button>
                    <a class="icon-action icon-action-download" href="{{ route('files.download', $item) }}" aria-label="Descargar {{ $item->original_name }}" title="Descargar archivo"><i class="bi bi-download" aria-hidden="true"></i></a>
                </div>
            </article>
        @empty
            <p class="empty">No hay archivos en esta categoría o búsqueda.</p>
        @endforelse
    </div>
    @if($items->hasPages())
        <nav class="pagination" aria-label="Paginas de archivos">
            @if($items->onFirstPage())<span aria-disabled="true">Anterior</span>@else<a href="{{ $items->previousPageUrl() }}" rel="prev">Anterior</a>@endif
            <strong>Pagina {{ $items->currentPage() }} de {{ $items->lastPage() }}</strong>
            @if($items->hasMorePages())<a href="{{ $items->nextPageUrl() }}" rel="next">Siguiente</a>@else<span aria-disabled="true">Siguiente</span>@endif
        </nav>
    @endif
</section>

{{-- Modal integrado para previsualización in-situ --}}
<div id="file-preview-modal" class="responsiva-preview-modal" hidden>
    <button type="button" class="responsiva-preview-backdrop" onclick="closeFilePreview()"></button>
    <div class="file-preview-card">
        <header class="file-preview-header">
            <h2 id="file-preview-title"><i class="bi bi-file-earmark" aria-hidden="true"></i> <span>Vista previa</span></h2>
            <div style="display:flex;align-items:center;gap:10px;">
                <a id="file-preview-download-btn" href="#" class="button button-secondary" style="height:36px;padding:0 12px;font-size:13px;"><i class="bi bi-download"></i> Descargar</a>
                <button type="button" class="file-preview-close" onclick="closeFilePreview()" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
            </div>
        </header>
        <div id="file-preview-container" class="file-preview-body">
            {{-- Inyectado dinámicamente --}}
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyFileDownloadLink(url, btn) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            const originalIcon = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2" style="color:#10b981;"></i>';
            btn.title = '¡Enlace copiado!';
            setTimeout(() => {
                btn.innerHTML = originalIcon;
                btn.title = 'Copiar enlace de descarga';
            }, 1800);
        });
    } else {
        prompt('Copia el enlace:', url);
    }
}

function openFilePreview(url, name, type) {
    const modal = document.getElementById('file-preview-modal');
    const title = document.querySelector('#file-preview-title span');
    const container = document.getElementById('file-preview-container');
    const downloadBtn = document.getElementById('file-preview-download-btn');

    title.textContent = name;
    downloadBtn.href = url.replace('/vista', '/descargar');
    container.innerHTML = '';

    if (type === 'image') {
        const img = document.createElement('img');
        img.src = url;
        img.alt = name;
        container.appendChild(img);
    } else if (type === 'audio') {
        const audio = document.createElement('audio');
        audio.controls = true;
        audio.autoplay = true;
        audio.src = url;
        container.appendChild(audio);
    } else if (type === 'video') {
        const video = document.createElement('video');
        video.controls = true;
        video.autoplay = true;
        video.src = url;
        container.appendChild(video);
    } else {
        // PDF o código/texto
        const iframe = document.createElement('iframe');
        iframe.src = url;
        container.appendChild(iframe);
    }

    modal.hidden = false;
    document.body.style.overflow = 'hidden';
}

function closeFilePreview() {
    const modal = document.getElementById('file-preview-modal');
    const container = document.getElementById('file-preview-container');
    container.innerHTML = '';
    modal.hidden = true;
    document.body.style.overflow = '';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeFilePreview();
});
</script>
@endpush
@endsection

