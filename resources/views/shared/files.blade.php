<section class="panel">
    <div class="panel-header">
        <div><p class="eyebrow">Documentos</p><h2>Archivos adjuntos</h2></div>
        <strong>{{ $files->count() }}</strong>
    </div>
    <div class="file-list">
        @forelse ($files as $file)
            <div class="file-download">
                <span>{{ strtoupper(pathinfo($file->original_name, PATHINFO_EXTENSION) ?: 'DOC') }}</span>
                <div>
                    <strong>{{ $file->label ?: $file->original_name }}</strong>
                    <small>{{ $file->original_name }} · {{ $file->file_size ? number_format($file->file_size / 1024, 1).' KB' : 'Tamano no disponible' }} · {{ $file->uploaded_at?->format('d/m/Y H:i') }}</small>
                </div>
                @if($file->isPreviewable())
                    <a href="{{ route('asset-files.preview', $file) }}" target="_blank" rel="noopener noreferrer" title="Ver archivo" aria-label="Ver archivo"><i class="bi bi-eye"></i><span class="sr-only">Ver</span></a>
                @endif
                <a href="{{ route('asset-files.download', $file) }}"><b>Descargar</b></a>
                @can('delete', $asset)
                    <form method="POST" action="{{ route('asset-files.destroy', $file) }}" onsubmit="return confirm('¿Eliminar este archivo?');">
                        @csrf @method('DELETE')
                        <button class="file-delete" type="submit" aria-label="Eliminar archivo">Eliminar</button>
                    </form>
                @endcan
            </div>
        @empty
            <p class="empty">Todavia no hay archivos adjuntos.</p>
        @endforelse
    </div>
    @can('upload', $asset)
        <form class="upload-form" method="POST" enctype="multipart/form-data" action="{{ route('asset-files.store', [$assetType, $asset->id]) }}">
            @csrf
            <label><span>Archivo</span><input type="file" name="file" required></label>
            <label><span>Etiqueta opcional</span><input name="label" maxlength="120" placeholder="Ej. Factura o acta de entrega"></label>
            <button class="button button-secondary" type="submit">Adjuntar archivo</button>
        </form>
    @endcan
</section>
