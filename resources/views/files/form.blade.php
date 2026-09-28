@extends('layouts.app')
@section('title', 'Archivo')
@section('page-title', $fileCatalog->exists ? 'Editar archivo' : 'Subir archivo')
@section('content')
@include('shared.validation')
<form id="file-catalog-form" class="asset-form" method="POST" enctype="multipart/form-data" action="{{ route('files.'.($fileCatalog->exists ? 'update' : 'store'), $fileCatalog->exists ? $fileCatalog : []) }}">
    @csrf
    @if($fileCatalog->exists) @method('PUT') @endif
    <section class="form-section">
        <div class="section-heading">
            <div><p class="eyebrow">Identificacion</p><h2>Datos del archivo</h2></div>
            <span class="section-number">01</span>
        </div>
        <div class="form-grid">
            <label>
                <span>Nombre descriptivo</span>
                <input id="alias_name_input" name="alias_name" maxlength="255" value="{{ old('alias_name', $fileCatalog->alias_name) }}" placeholder="Ej. Instalador de soporte remoto">
                <small>Este nombre facilita encontrarlo; el nombre original se conserva.</small>
            </label>
            @unless($fileCatalog->exists)
                <div>
                    <label style="display:block;margin-bottom:6px;"><span>Archivo *</span></label>
                    <div id="file-dropzone" class="file-dropzone">
                        <input id="file-input" type="file" name="file" required>
                        <div class="file-dropzone-icon"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i></div>
                        <div class="file-dropzone-text">
                            <strong>Haz clic o arrastra tu archivo aquí</strong>
                            <small>Sin límite de tamaño &middot; Cualquier formato admitido</small>
                        </div>
                        <div id="file-selected-badge" class="file-selected-badge" style="display:none;">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                            <span id="file-selected-name"></span>
                        </div>
                    </div>
                </div>
            @else
                <label><span>Nombre original</span><input value="{{ $fileCatalog->original_name }}" readonly></label>
            @endunless
        </div>

        <div id="upload-progress-wrapper" class="upload-progress-wrapper" style="display:none;">
            <div class="upload-progress-header">
                <span id="upload-status-text">Subiendo archivo al servidor...</span>
                <strong id="upload-percent">0%</strong>
            </div>
            <div class="upload-progress-bar">
                <div id="upload-progress-fill" class="upload-progress-fill"></div>
            </div>
            <div class="upload-progress-details">
                <span id="upload-transferred">0 MB</span>
                <span id="upload-hint">Por favor, no cierres esta ventana durante la transferencia.</span>
            </div>
        </div>

        <label class="textarea-field" style="margin-top:16px;"><span>Comentarios</span><textarea name="comments">{{ old('comments', $fileCatalog->comments) }}</textarea></label>
    </section>
    <footer class="form-footer">
        <a class="button button-secondary" href="{{ route('files.index') }}">Cancelar</a>
        <button id="submit-btn" type="submit" class="button button-primary">
            <i class="bi bi-check-lg" aria-hidden="true"></i> <span id="submit-btn-text">Guardar</span>
        </button>
    </footer>
</form>

@unless($fileCatalog->exists)
@push('scripts')
<script>
(() => {
    const form = document.getElementById('file-catalog-form');
    const fileInput = document.getElementById('file-input');
    const dropzone = document.getElementById('file-dropzone');
    const badge = document.getElementById('file-selected-badge');
    const badgeName = document.getElementById('file-selected-name');
    const aliasInput = document.getElementById('alias_name_input');
    const progressWrapper = document.getElementById('upload-progress-wrapper');
    const progressFill = document.getElementById('upload-progress-fill');
    const percentText = document.getElementById('upload-percent');
    const statusText = document.getElementById('upload-status-text');
    const transferredText = document.getElementById('upload-transferred');
    const submitBtn = document.getElementById('submit-btn');
    const submitBtnText = document.getElementById('submit-btn-text');

    if (!fileInput || !dropzone) return;

    function formatBytes(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function handleFileSelection(file) {
        if (!file) return;
        badgeName.textContent = file.name + ' (' + formatBytes(file.size) + ')';
        badge.style.display = 'inline-flex';

        if (aliasInput && !aliasInput.value.trim()) {
            const cleanName = file.name.replace(/\.[^/.]+$/, "").replace(/[-_]/g, " ");
            aliasInput.value = cleanName.charAt(0).toUpperCase() + cleanName.slice(1);
        }
    }

    fileInput.addEventListener('change', () => {
        if (fileInput.files && fileInput.files[0]) {
            handleFileSelection(fileInput.files[0]);
        }
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.add('is-dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropzone.classList.remove('is-dragover');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelection(e.dataTransfer.files[0]);
        }
    });

    form.addEventListener('submit', (e) => {
        if (!fileInput.files || !fileInput.files.length) return;

        const file = fileInput.files[0];
        // Si el archivo es mayor a 2 MB, mostrar barra de progreso con XHR
        if (file && file.size > 2 * 1024 * 1024) {
            e.preventDefault();

            progressWrapper.style.display = 'block';
            submitBtn.disabled = true;
            submitBtnText.textContent = 'Subiendo...';
            statusText.textContent = 'Transfiriendo archivo (' + formatBytes(file.size) + ')...';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.addEventListener('progress', (ev) => {
                if (ev.lengthComputable) {
                    const percent = Math.round((ev.loaded / ev.total) * 100);
                    progressFill.style.width = percent + '%';
                    percentText.textContent = percent + '%';
                    transferredText.textContent = formatBytes(ev.loaded) + ' de ' + formatBytes(ev.total);
                    if (percent === 100) {
                        statusText.textContent = 'Procesando y almacenando en servidor...';
                    }
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 400) {
                    // Si responde con redirección o JSON de éxito
                    window.location.href = "{{ route('files.index') }}";
                } else {
                    submitBtn.disabled = false;
                    submitBtnText.textContent = 'Guardar';
                    statusText.textContent = 'Error al subir (Código ' + xhr.status + ')';
                    progressFill.style.background = '#d9000d';
                    alert('Ocurrió un error al subir el archivo (' + xhr.status + '). Por favor verifica tu conexión o el servidor.');
                }
            });

            xhr.addEventListener('error', () => {
                submitBtn.disabled = false;
                submitBtnText.textContent = 'Guardar';
                statusText.textContent = 'Error de conexión durante la subida';
                alert('Se interrumpió la conexión al subir el archivo.');
            });

            const formData = new FormData(form);
            xhr.send(formData);
        }
    });
})();
</script>
@endpush
@endunless
@endsection

