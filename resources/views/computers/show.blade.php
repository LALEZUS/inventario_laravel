@extends('layouts.app')

@section('title', $computer->name)
@section('page-title', 'Detalle de computadora')

@section('content')
    <div class="detail-toolbar">
        <a class="back-link" href="{{ route('computers.index') }}">&larr; Volver a computadoras</a>
        <div class="page-actions">
            @can('update', $computer)<a class="button button-primary" href="{{ route('computers.edit', $computer) }}">Editar computadora</a>@endcan
            @can('delete', $computer)
                <form method="POST" action="{{ route('computers.destroy', $computer) }}" onsubmit="return confirm('¿Eliminar esta computadora? Los perifericos quedaran sin vincular.');">
                    @csrf @method('DELETE')
                    <button class="button button-danger" type="submit">Eliminar</button>
                </form>
            @endcan
        </div>
    </div>
    <section class="asset-header">
        <div class="asset-icon">PC</div>
        <div class="asset-heading">
            <p class="eyebrow">Asset profile</p>
            <h2>{{ $computer->name }}</h2>
            <p>{{ $computer->brand ?: 'Sin marca' }} · {{ $computer->model ?: 'Sin modelo' }}</p>
        </div>
        <span class="status large">{{ $computer->status }}</span>
    </section>

    <section class="asset-tools-grid">
        <article class="asset-tool-card qr-tool-card">
            <div><p class="eyebrow">Acceso rapido</p><h2>Codigo QR del activo</h2><p>Abre esta ficha protegida al escanearlo.</p></div>
            <img src="{{ route('computers.qr', $computer) }}" alt="Codigo QR de {{ $computer->name }}" width="170" height="170">
            <a class="button button-secondary" href="{{ route('computers.qr', ['computer' => $computer, 'download' => 1]) }}">Descargar QR</a>
        </article>
        @can('upload', $computer)
            <article class="asset-tool-card responsiva-tool-card">
                <div class="document-mark">PDF</div>
                <div><p class="eyebrow">Documento de resguardo</p><h2>Generar responsiva</h2><p>Conserva el formato oficial, incluye las fotos del equipo y guarda una copia en el activo.</p></div>
                @include('computers._responsiva-form', ['computer' => $computer])
            </article>
        @endcan
    </section>

    <section class="detail-grid">
        @foreach ([
            'Usuario asignado' => $computer->assigned_to,
            'Zona' => $computer->zone ?: $computer->location,
            'Folio' => $computer->code,
            'Serie' => $computer->serial,
            'Procesador' => $computer->processor,
            'RAM' => $computer->ram,
            'Almacenamiento' => $computer->storage,
            'Sistema operativo' => $computer->os,
            'Version' => $computer->os_version,
            'Arquitectura' => $computer->architecture,
            'BIOS / UEFI' => $computer->bios,
            'Placa base' => $computer->motherboard,
            'Graficos' => $computer->gpu,
            'MAC' => $computer->mac_address,
            'Arranque seguro' => $computer->secure_boot,
            'TPM' => $computer->tpm,
            'ID de AnyDesk' => $computer->anydesk_id,
            'ID de RustDesk' => $computer->rustdesk_id,
            'Valor del equipo' => $computer->value !== null ? '$'.number_format((float) $computer->value, 2, '.', ',') : null,
        ] as $label => $value)
            <article><span>{{ $label }}</span><strong>{{ $value ?: '-' }}</strong></article>
        @endforeach
        @can('viewSensitive', $computer)
            <article class="sensitive-detail">
                <span>Contraseña de administrador</span>
                <strong class="secret-value" data-secret-url="{{ route('computers.secrets', $computer) }}" data-secret-field="admin_password">{{ $computer->admin_password ? '********' : '-' }}</strong>
                @if($computer->admin_password)<button class="reveal-secret" type="button"><i class="bi bi-eye"></i> Mostrar</button>@endif
            </article>
        @endcan
    </section>

    @php($photos = $files->filter(fn ($file) => $file->isImage())->values())
    <section class="panel computer-photos-panel">
        <div class="panel-header">
            <div><p class="eyebrow">Evidencia visual</p><h2>Fotos del equipo</h2><p>Agrega imágenes del equipo, etiquetas o daños visibles.</p></div>
            <strong>{{ $photos->count() }}</strong>
        </div>
        <div class="photo-grid">
            @forelse ($photos as $photo)
                <figure class="asset-photo">
                    <a href="{{ route('asset-files.preview', $photo) }}" data-photo-preview data-photo-title="{{ $photo->label ?: $photo->original_name }}">
                        <img src="{{ route('asset-files.preview', $photo) }}" alt="{{ $photo->label ?: $photo->original_name }}" loading="lazy">
                    </a>
                    <figcaption>
                        <div class="photo-caption-title">
                            <strong>{{ $photo->label ?: $photo->original_name }}</strong>
                            @can('update', $computer)
                                <details class="photo-label-edit">
                                    <summary title="Editar etiqueta" aria-label="Editar etiqueta"><i class="bi bi-pencil"></i></summary>
                                    <form class="photo-label-form" method="POST" action="{{ route('asset-files.label.update', $photo) }}">
                                        @csrf @method('PUT')
                                        <label class="sr-only" for="photo-label-{{ $photo->id }}">Etiqueta de la foto</label>
                                        <input id="photo-label-{{ $photo->id }}" name="label" value="{{ $photo->label }}" maxlength="120" placeholder="Agregar etiqueta">
                                        <button type="submit" title="Guardar etiqueta" aria-label="Guardar etiqueta"><i class="bi bi-check2"></i></button>
                                    </form>
                                </details>
                            @endcan
                        </div>
                        <small>{{ $photo->uploaded_at?->format('d/m/Y H:i') }}</small>
                        <div class="photo-actions">
                            <a href="{{ route('asset-files.download', $photo) }}">Descargar</a>
                            @can('delete', $computer)
                                <form method="POST" action="{{ route('asset-files.destroy', $photo) }}" onsubmit="return confirm('¿Eliminar esta foto?');">
                                    @csrf @method('DELETE')
                                    <button type="submit">Eliminar</button>
                                </form>
                            @endcan
                        </div>
                    </figcaption>
                </figure>
            @empty
                <p class="empty">Todavía no hay fotos de este equipo.</p>
            @endforelse
        </div>
        <dialog class="photo-lightbox" id="computer-photo-lightbox" aria-label="Vista ampliada de la foto">
            <button class="photo-lightbox-close" type="button" aria-label="Cerrar vista ampliada"><i class="bi bi-x-lg"></i></button>
            <img src="" alt="">
            <strong></strong>
        </dialog>
        @can('upload', $computer)
            <form class="upload-form photo-upload-form" method="POST" enctype="multipart/form-data" action="{{ route('asset-files.photos.store', ['assetType' => 'inventory', 'assetId' => $computer->id]) }}">
                @csrf
                <label><span>Seleccionar fotos</span><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required></label>
                <label><span>Etiqueta opcional</span><input name="label" maxlength="120" placeholder="Ej. Frente, serie o daño"></label>
                <button class="button button-secondary" type="submit">Subir fotos</button>
            </form>
        @endcan
    </section>

    @include('shared.files', ['files' => $files->reject(fn ($file) => $file->isImage())->values(), 'asset' => $computer, 'assetType' => 'inventory'])

    @include('credentials._secret-script')

    @push('scripts')
    <script>
    (() => {
        const dialog = document.getElementById('computer-photo-lightbox');
        if (!dialog) return;
        const image = dialog.querySelector('img');
        const caption = dialog.querySelector('strong');

        document.querySelectorAll('[data-photo-preview]').forEach((link) => link.addEventListener('click', (event) => {
            event.preventDefault();
            image.src = link.href;
            image.alt = link.dataset.photoTitle || 'Foto del equipo';
            caption.textContent = link.dataset.photoTitle || '';
            dialog.showModal();
        }));
        dialog.querySelector('.photo-lightbox-close').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
        dialog.addEventListener('close', () => {
            image.removeAttribute('src');
            image.alt = '';
        });
    })();
    </script>
    <script>
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.reveal-secret');
        const value = button?.previousElementSibling;
        if (!button || !value?.dataset.secretUrl) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        const hidden = value.dataset.visible !== 'true';
        if (!hidden) {
            value.textContent = '********';
            value.dataset.visible = 'false';
            button.innerHTML = '<i class="bi bi-eye"></i> Mostrar';
            return;
        }
        button.disabled = true;
        try {
            const response = await fetch(value.dataset.secretUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error('No autorizado');
            const secrets = await response.json();
            value.textContent = secrets[value.dataset.secretField] || '-';
            value.dataset.visible = 'true';
            button.innerHTML = '<i class="bi bi-eye-slash"></i> Ocultar';
        } catch (error) {
            value.textContent = 'No disponible';
        } finally {
            button.disabled = false;
        }
    }, true);
    </script>
    @endpush

    <section class="panel">
        <div class="panel-header"><div><p class="eyebrow">Documentos</p><h2>Archivo de especificaciones</h2></div></div>
        @if ($computer->nfo_file)
            <a class="file-download" href="{{ route('computers.nfo.download', $computer) }}">
                <span>NFO</span>
                <div><strong>{{ basename($computer->nfo_file) }}</strong><small>Descarga protegida por sesion</small></div>
                <b>Descargar</b>
            </a>
        @else
            <p class="empty">Esta computadora no tiene un archivo NFO guardado.</p>
        @endif
    </section>

    <section class="panel">
        <div class="panel-header"><div><p class="eyebrow">Relacion de activos</p><h2>Perifericos vinculados</h2></div><strong>{{ $computer->peripherals->count() }}</strong></div>
        <div class="recent-list">
            @forelse ($computer->peripherals as $peripheral)
                <a class="static-row" href="{{ route('peripherals.show', $peripheral) }}"><div><strong>{{ $peripheral->name }}</strong><small>{{ $peripheral->brand }} {{ $peripheral->model }} · {{ $peripheral->serial ?: 'Sin serie' }}</small></div><span class="status">{{ $peripheral->status }}</span></a>
            @empty
                <p class="empty">Esta computadora todavia no tiene perifericos vinculados.</p>
            @endforelse
        </div>
    </section>

    <section class="panel operational-panel" id="assignments">
        <div class="panel-header"><div><p class="eyebrow">Custodia y documentos</p><h2>Asignaciones formales</h2><p>Historial de entregas, devoluciones y responsivas.</p></div><strong>{{ $computer->assignments->count() }}</strong></div>
        @can('update', $computer)
            <details class="operation-form-disclosure"><summary><i class="bi bi-person-plus"></i> Registrar asignacion</summary>
                <form method="POST" action="{{route('computers.assignments.store',$computer)}}" class="operation-form">@csrf
                    <label><span>Empleado *</span>
                        <select name="employee_id" required>
                            <option value="">Seleccionar</option>
                            <?php
                                $activeEmployees = $employees->where('status', 'Activo');
                                $inactiveEmployees = $employees->where('status', '!=', 'Activo');
                            ?>
                            @if ($activeEmployees->isNotEmpty())
                                <optgroup label="Empleados activos">
                                    @foreach ($activeEmployees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->full_name }} · {{ $employee->department }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if ($inactiveEmployees->isNotEmpty())
                                <optgroup label="Empleados inactivos">
                                    @foreach ($inactiveEmployees as $employee)
                                        <option value="{{ $employee->id }}" class="option-inactive">{{ $employee->full_name }} · {{ $employee->department }} (Inactivo)</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        </select>
                    </label>
                    <label><span>Fecha de entrega *</span><input type="date" name="date_assigned" value="{{now()->format('Y-m-d')}}" required></label>
                    <label><span>Condicion</span><select name="condition_on_assign"><option>Bueno</option><option>Nuevo</option><option>Usado</option><option>Con detalles</option></select></label>
                    <label><span>Area</span><input name="department" maxlength="100"></label>
                    <label class="wide"><span>Notas</span><textarea name="notes"></textarea></label>
                    <button class="button button-primary">Guardar asignacion</button>
                </form>
            </details>
        @endcan
        <div class="operation-timeline">
            @forelse($computer->assignments as $assignment)
                <article class="operation-record {{$assignment->date_returned?'closed':'active'}}">
                    <span class="operation-icon"><i class="bi {{$assignment->date_returned?'bi-arrow-return-left':'bi-person-check'}}"></i></span>
                    <div class="operation-main"><header><div><strong>{{$assignment->assigned_to}}</strong><small>{{$assignment->department ?: 'Sin area'}} · Entrega {{$assignment->date_assigned->format('d/m/Y')}}</small></div><span class="status">{{$assignment->date_returned?'Devuelto':'Asignado'}}</span></header>
                        @if($assignment->notes)<p>{{$assignment->notes}}</p>@endif
                        @if($assignment->date_returned)<small>Devolucion: {{$assignment->date_returned->format('d/m/Y')}} · {{$assignment->condition_on_return ?: 'Condicion no indicada'}}</small>@endif
                        <div class="operation-actions">
                            @can('upload',$computer)@include('computers._responsiva-form', ['computer'=>$computer, 'assignmentId'=>$assignment->id, 'compact'=>true])@endcan
                            @foreach($assignment->responsivas as $document)<a class="button button-secondary" href="{{route('asset-files.download',$document)}}"><i class="bi bi-download"></i> {{$document->original_name}}</a>@endforeach
                            @if(!$assignment->date_returned) @can('update',$computer)<details class="inline-operation"><summary>Registrar devolucion</summary><form method="POST" action="{{route('computers.assignments.return',[$computer,$assignment])}}">@csrf @method('PUT')<input type="date" name="date_returned" value="{{now()->format('Y-m-d')}}" required><select name="condition_on_return"><option>Bueno</option><option>Con detalles</option><option>Dañado</option></select><input name="comments" placeholder="Comentarios"><button class="button button-primary">Confirmar</button></form></details>@endcan @endif
                            @if(auth()->user()->role === 'admin')<form method="POST" action="{{route('computers.assignments.destroy',[$computer,$assignment])}}" onsubmit="return confirm('¿Eliminar esta asignación?');">@csrf @method('DELETE')<button class="button button-danger" type="submit"><i class="bi bi-trash"></i> Eliminar</button></form>@endif
                        </div>
                    </div>
                </article>
            @empty<p class="empty">No hay asignaciones formales registradas.</p>@endforelse
        </div>
    </section>

    <section class="panel operational-panel" id="maintenance">
        <div class="panel-header"><div><p class="eyebrow">Soporte tecnico</p><h2>Bitacora de mantenimiento</h2><p>Servicios realizados y proximas intervenciones.</p></div><strong>{{ $computer->maintenanceLogs->count() }}</strong></div>
        @can('update',$computer)
            <details class="operation-form-disclosure"><summary><i class="bi bi-tools"></i> Agregar mantenimiento</summary>
                <form method="POST" action="{{route('computers.maintenance.store',$computer)}}" class="operation-form">@csrf
                    <label><span>Fecha *</span><input type="date" name="date" value="{{now()->format('Y-m-d')}}" required></label>
                    <label><span>Tipo</span><select name="category"><option>Preventivo</option><option>Correctivo</option><option>Diagnostico</option><option>Actualizacion</option></select></label>
                    <label><span>Estado *</span><select name="status" required><option>Completado</option><option>Programado</option><option>En proceso</option><option>Cancelado</option></select></label>
                    <label><span>Tecnico</span><input name="technician" maxlength="100"></label><label><span>Proveedor</span><input name="provider" maxlength="120"></label>
                    <label><span>Costo</span><input type="number" step="0.01" min="0" name="cost"></label><label><span>Proximo mantenimiento</span><input type="date" name="next_date"></label>
                    <label class="wide"><span>Trabajo realizado *</span><textarea name="description" required></textarea></label><label class="wide"><span>Diagnostico</span><textarea name="diagnosis"></textarea></label>
                    <button class="button button-primary">Guardar mantenimiento</button>
                </form>
            </details>
        @endcan
        <div class="operation-timeline">
            @forelse($computer->maintenanceLogs as $maintenance)
                <article class="operation-record">
                    <span class="operation-icon"><i class="bi bi-wrench-adjustable"></i></span>
                    <div class="operation-main">
                        <header><div><strong>{{$maintenance->category ?: 'Mantenimiento'}} · {{$maintenance->date->format('d/m/Y')}}</strong><small>{{$maintenance->technician ?: 'Tecnico no indicado'}}{{$maintenance->provider?' · '.$maintenance->provider:''}}</small></div><span class="status">{{$maintenance->status}}</span></header>
                        <p>{{$maintenance->description}}</p>
                        @if($maintenance->diagnosis)<small>Diagnostico: {{$maintenance->diagnosis}}</small>@endif
                        <div class="operation-meta"><span><i class="bi bi-cash"></i> ${{number_format((float)$maintenance->cost,2)}}</span>@if($maintenance->next_date)<span><i class="bi bi-calendar-event"></i> Proximo: {{$maintenance->next_date->format('d/m/Y')}}</span>@endif</div>
                        <div class="operation-actions">
                            @can('update',$computer)
                                <details class="inline-operation"><summary><i class="bi bi-pencil"></i> Editar</summary><form method="POST" action="{{route('computers.maintenance.update',[$computer,$maintenance])}}">@csrf @method('PUT')<input type="date" name="date" value="{{$maintenance->date->format('Y-m-d')}}" required><input name="category" value="{{$maintenance->category}}" placeholder="Tipo"><select name="status" required>@foreach(['Programado','En proceso','Completado','Cancelado'] as $status)<option @selected($maintenance->status===$status)>{{$status}}</option>@endforeach</select><input name="technician" value="{{$maintenance->technician}}" placeholder="Tecnico"><input name="provider" value="{{$maintenance->provider}}" placeholder="Proveedor"><input type="number" step="0.01" min="0" name="cost" value="{{$maintenance->cost}}" placeholder="Costo"><input type="date" name="next_date" value="{{$maintenance->next_date?->format('Y-m-d')}}"><textarea name="description" required>{{$maintenance->description}}</textarea><textarea name="diagnosis" placeholder="Diagnostico">{{$maintenance->diagnosis}}</textarea><button class="button button-primary">Guardar</button></form></details>
                            @endcan
                            @if(auth()->user()->role === 'admin')<form method="POST" action="{{route('computers.maintenance.destroy',[$computer,$maintenance])}}" onsubmit="return confirm('¿Eliminar este mantenimiento?');">@csrf @method('DELETE')<button class="button button-danger" type="submit"><i class="bi bi-trash"></i> Eliminar</button></form>@endif
                        </div>
                    </div>
                </article>
            @empty<p class="empty">No hay mantenimientos registrados.</p>@endforelse
        </div>
    </section>

    @can('viewAudit', \App\Models\HardwareAsset::class)
        <section class="panel">
            <div class="panel-header"><div><p class="eyebrow">Trazabilidad</p><h2>Bitacora del activo</h2></div><strong>{{ $auditLogs->count() }}</strong></div>
            <div class="audit-timeline">
                @forelse ($auditLogs as $log)
                    <article>
                        <span class="audit-dot"></span>
                        <div><strong>{{ ucfirst($log->action) }} por {{ $log->user_name ?: 'Sistema' }}</strong><small>{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->role ?: 'sin rol' }} · {{ $log->ip_address ?: 'sin IP' }}</small></div>
                    </article>
                @empty
                    <p class="empty">No hay movimientos registrados para este activo.</p>
                @endforelse
            </div>
        </section>
    @endcan

    <div class="responsiva-preview-modal" data-responsiva-preview-modal hidden>
        <button class="responsiva-preview-backdrop" type="button" data-responsiva-preview-close aria-label="Cerrar vista previa"></button>
        <section class="responsiva-preview-card" role="dialog" aria-modal="true" aria-labelledby="responsiva-preview-title">
            <header>
                <div><p class="eyebrow">Documento antes de guardar</p><h2 id="responsiva-preview-title">Vista previa de la responsiva</h2><p>Revisa el tamaño y acomodo de las fotografías. Cierra esta ventana para cambiar las opciones.</p></div>
                <button type="button" class="responsiva-preview-close" data-responsiva-preview-close aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
            </header>
            <div class="responsiva-preview-loading" data-responsiva-preview-loading><i class="bi bi-arrow-repeat instant-search-spinner"></i> Preparando vista previa...</div>
            <iframe title="Vista previa PDF de la responsiva" data-responsiva-preview-frame></iframe>
            <footer>
                <button class="button button-secondary" type="button" data-responsiva-preview-close>Seguir acomodando</button>
                <button class="button button-primary" type="button" data-responsiva-preview-download><i class="bi bi-file-earmark-arrow-down"></i> Generar y descargar</button>
            </footer>
        </section>
    </div>
@endsection
@push('scripts')
<script>
const responsivaModal = document.querySelector('[data-responsiva-preview-modal]');
const responsivaFrame = responsivaModal?.querySelector('[data-responsiva-preview-frame]');
const responsivaLoading = responsivaModal?.querySelector('[data-responsiva-preview-loading]');
const responsivaDownload = responsivaModal?.querySelector('[data-responsiva-preview-download]');
let activeResponsivaForm = null;
let responsivaPreviewUrl = null;

const setResponsivaBusy = (button, busy, label) => {
    if (!button) return;
    if (busy) button.dataset.originalContent = button.innerHTML;
    button.disabled = busy;
    button.innerHTML = busy
        ? `<i class="bi bi-arrow-repeat instant-search-spinner" aria-hidden="true"></i> ${label}`
        : button.dataset.originalContent;
};

const responsivaRequest = async (form, url) => {
    const response = await fetch(url, {
        method: 'POST',
        body: new FormData(form),
        headers: {Accept: 'application/pdf'},
    });
    if (!response.ok) throw new Error('No se pudo preparar la responsiva.');
    return response;
};

const downloadResponsiva = async (form, button) => {
    setResponsivaBusy(button, true, 'Generando...');
    try {
        const response = await responsivaRequest(form, form.action);
        const blob = await response.blob();
        const disposition = response.headers.get('Content-Disposition') || '';
        const match = disposition.match(/filename="([^"]+)"/);
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = match ? match[1] : 'Responsiva.pdf';
        link.click();
        window.setTimeout(() => URL.revokeObjectURL(link.href), 1000);
        window.setTimeout(() => window.location.reload(), 500);
    } catch (error) {
        window.showInventoryToast?.(error.message, 'error');
        setResponsivaBusy(button, false);
    }
};

const closeResponsivaPreview = () => {
    if (!responsivaModal) return;
    responsivaModal.hidden = true;
    document.body.classList.remove('has-responsiva-preview');
    if (responsivaFrame) responsivaFrame.removeAttribute('src');
    if (responsivaPreviewUrl) URL.revokeObjectURL(responsivaPreviewUrl);
    responsivaPreviewUrl = null;
};

document.querySelectorAll('.responsiva-form').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        downloadResponsiva(form, event.submitter || form.querySelector('[type="submit"]'));
    });

    form.querySelector('[data-responsiva-preview]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        activeResponsivaForm = form;
        responsivaModal.hidden = false;
        responsivaLoading.hidden = false;
        document.body.classList.add('has-responsiva-preview');
        setResponsivaBusy(button, true, 'Preparando...');
        try {
            const response = await responsivaRequest(form, form.dataset.previewAction);
            responsivaPreviewUrl = URL.createObjectURL(await response.blob());
            responsivaFrame.onload = () => { responsivaLoading.hidden = true; };
            responsivaFrame.src = responsivaPreviewUrl;
        } catch (error) {
            closeResponsivaPreview();
            window.showInventoryToast?.(error.message, 'error');
        } finally {
            setResponsivaBusy(button, false);
        }
    });
});

document.querySelectorAll('[data-responsiva-preview-close]').forEach((button) => button.addEventListener('click', closeResponsivaPreview));
responsivaDownload?.addEventListener('click', () => activeResponsivaForm && downloadResponsiva(activeResponsivaForm, responsivaDownload));
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && responsivaModal && !responsivaModal.hidden) closeResponsivaPreview();
});
</script>
@endpush
