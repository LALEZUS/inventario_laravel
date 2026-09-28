@php($editing = $computer->exists)

<a class="back-link" href="{{ $editing ? route('computers.show', $computer) : route('computers.index') }}">&larr; Volver</a>

@if ($errors->any())
    <div class="validation-summary" role="alert">
        <strong>Revisa los datos del formulario.</strong>
        <ul>
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form id="computer-form" method="POST" enctype="multipart/form-data" action="{{ $editing ? route('computers.update', $computer) : route('computers.store') }}" class="asset-form">
    @csrf
    @if ($editing) @method('PUT') @endif

    <section class="form-section nfo-section">
        <div class="section-heading">
            <div><p class="eyebrow">Autollenado opcional</p><h2>Archivo de especificaciones NFO</h2></div>
            <span class="section-number">01</span>
        </div>
        <div class="nfo-upload">
            <div>
                <label for="nfo_file">Seleccionar archivo .nfo</label>
                <p>Se completan modelo, marca, procesador, RAM, disco, Windows, BIOS, red y seguridad. Puedes corregir cualquier dato antes de guardar.</p>
                @if ($editing && $computer->nfo_file)
                    <a href="{{ route('computers.nfo.download', $computer) }}">Descargar NFO actual</a>
                @endif
            </div>
            <input id="nfo_file" name="nfo_file" type="file" accept=".nfo">
        </div>
        <div id="nfo-status" class="nfo-status" aria-live="polite">No se ha seleccionado un archivo.</div>
    </section>

    <section class="form-section">
        <div class="section-heading"><div><p class="eyebrow">Identificacion</p><h2>Datos principales</h2></div><span class="section-number">02</span></div>
        <div class="form-grid three-columns">
            <label><span>Nombre del equipo *</span><input name="name" value="{{ old('name', $computer->name) }}" maxlength="100" placeholder="Ej. ThinkCentre M70Q"></label>
            <label><span>Formato</span><select name="format"><option value="">Seleccionar</option>@foreach (['Laptop / Notebook', 'Desktop / PC de escritorio', 'Workstation', 'Servidor'] as $format)<option value="{{ $format }}" @selected(old('format', $computer->format) === $format)>{{ $format }}</option>@endforeach</select></label>
            <label><span>Estado *</span><select name="status" required>@foreach (['DISPONIBLE', 'ENTREGADO', 'MANTENIMIENTO', 'BAJA'] as $status)<option value="{{ $status }}" @selected(old('status', $computer->status ?: 'DISPONIBLE') === $status)>{{ $status }}</option>@endforeach</select></label>
            <label><span>Folio</span><input name="code" value="{{ old('code', $computer->code) }}" maxlength="50"></label>
            <label><span>Serie</span><input name="serial" value="{{ old('serial', $computer->serial) }}" maxlength="100"></label>
            <label><span>Marca</span><input name="brand" value="{{ old('brand', $computer->brand) }}" maxlength="50"></label>
            <label><span>Modelo</span><input name="model" value="{{ old('model', $computer->model) }}" maxlength="100"></label>
            <input type="hidden" name="category" value="Equipo">
        </div>
    </section>

    <section class="form-section">
        <div class="section-heading"><div><p class="eyebrow">Responsable</p><h2>Asignacion y ubicacion</h2></div><span class="section-number">03</span></div>
        <div class="form-grid three-columns">
            <label><span>Empleado relacionado</span>
                <select name="employee_id">
                    <option value="">Sin empleado relacionado</option>
                    <?php
                        $activeEmployees = $employees->where('status', 'Activo');
                        $inactiveEmployees = $employees->where('status', '!=', 'Activo');
                    ?>
                    @if ($activeEmployees->isNotEmpty())
                        <optgroup label="Empleados activos">
                            @foreach ($activeEmployees as $employee)
                                <option value="{{ $employee->id }}" @selected((string) old('employee_id', $computer->employee_id) === (string) $employee->id)>{{ $employee->full_name }}{{ $employee->department ? ' - '.$employee->department : '' }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                    @if ($inactiveEmployees->isNotEmpty())
                        <optgroup label="Empleados inactivos">
                            @foreach ($inactiveEmployees as $employee)
                                <option value="{{ $employee->id }}" class="option-inactive" @selected((string) old('employee_id', $computer->employee_id) === (string) $employee->id)>{{ $employee->full_name }}{{ $employee->department ? ' - '.$employee->department : '' }} (Inactivo)</option>
                            @endforeach
                        </optgroup>
                    @endif
                </select>
            </label>
            <label><span>Usuario manual o anterior</span><input name="assigned_user" value="{{ old('assigned_user', $computer->assigned_user) }}" maxlength="150" placeholder="Solo si no aparece en empleados"></label>
            <label><span>Fecha de entrega</span><input name="delivery_date" type="date" value="{{ old('delivery_date', optional($computer->delivery_date)->format('Y-m-d')) }}"></label>
            <label><span>Zona</span><input name="zone" value="{{ old('zone', $computer->zone) }}" maxlength="50" placeholder="GDL, CDMX..."></label>
            <label><span>Ubicacion</span><input name="location" value="{{ old('location', $computer->location) }}" maxlength="100"></label>
        </div>
    </section>

    <section class="form-section">
        <div class="section-heading"><div><p class="eyebrow">Hardware</p><h2>Especificaciones tecnicas</h2></div><span class="section-number">04</span></div>
        <div class="form-grid three-columns">
            <label><span>Procesador</span><input name="processor" value="{{ old('processor', $computer->processor) }}" maxlength="100"></label>
            <label><span>RAM</span><input name="ram" value="{{ old('ram', $computer->ram) }}" maxlength="20"></label>
            <label><span>Disco / almacenamiento</span><input name="storage" value="{{ old('storage', $computer->storage) }}" maxlength="50"></label>
            <label><span>Sistema operativo</span><input name="os" value="{{ old('os', $computer->os) }}" maxlength="100"></label>
            <label><span>Version de Windows</span><input name="os_version" value="{{ old('os_version', $computer->os_version) }}" maxlength="120"></label>
            <label><span>Arquitectura</span><input name="architecture" value="{{ old('architecture', $computer->architecture) }}" maxlength="100"></label>
            <label><span>BIOS / UEFI</span><input name="bios" value="{{ old('bios', $computer->bios) }}" maxlength="255"></label>
            <label><span>Placa base</span><input name="motherboard" value="{{ old('motherboard', $computer->motherboard) }}" maxlength="150"></label>
            <label><span>Graficos / GPU</span><input name="gpu" value="{{ old('gpu', $computer->gpu) }}" maxlength="150"></label>
            <label><span>Adaptador de red</span><input name="network_adapter" value="{{ old('network_adapter', $computer->network_adapter) }}" maxlength="150"></label>
            <label><span>Direccion MAC</span><input name="mac_address" value="{{ old('mac_address', $computer->mac_address) }}" maxlength="80"></label>
            <label><span>Arranque seguro</span><input name="secure_boot" value="{{ old('secure_boot', $computer->secure_boot) }}" maxlength="80"></label>
            <label><span>TPM</span><input name="tpm" value="{{ old('tpm', $computer->tpm) }}" maxlength="120"></label>
            <label><span>Contrasena de administrador</span><input name="admin_password" type="password" maxlength="255" autocomplete="new-password" placeholder="{{ $editing && $computer->admin_password ? 'Dejar vacio para conservarla' : 'Opcional' }}"></label>
            <label><span>Valor del equipo</span><input name="value" type="number" value="{{ old('value', $computer->value) }}" min="0" max="999999999.99" step="0.01" inputmode="decimal" placeholder="Ej. 12500.00"></label>
        </div>
    </section>

    <section class="form-section">
        <div class="section-heading"><div><p class="eyebrow">Soporte</p><h2>Acceso remoto</h2></div><span class="section-number">05</span></div>
        <div class="form-grid three-columns">
            <label><span>ID de AnyDesk</span><input name="anydesk_id" value="{{ old('anydesk_id', $computer->anydesk_id) }}" maxlength="50" inputmode="numeric" autocomplete="off" placeholder="Ej. 123 456 789"></label>
            <label><span>ID de RustDesk</span><input name="rustdesk_id" value="{{ old('rustdesk_id', $computer->rustdesk_id) }}" maxlength="100" autocomplete="off" placeholder="Ej. 123 456 789"></label>
        </div>
    </section>

    <section class="form-section">
        <div class="section-heading"><div><p class="eyebrow">Software</p><h2>Instalaciones y notas</h2></div><span class="section-number">06</span></div>
        <div class="check-grid">
            @foreach (['has_office' => 'Microsoft Office', 'has_reader' => 'Lector PDF', 'has_winrar' => 'WinRAR', 'has_server' => 'Servidor', 'has_printer' => 'Impresora'] as $field => $label)
                <label><input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $computer->{$field}))><span>{{ $label }}</span></label>
            @endforeach
        </div>
        <label class="textarea-field"><span>Notas</span><textarea name="comments" rows="4">{{ old('comments', $computer->comments) }}</textarea></label>
    </section>

    <footer class="form-footer">
        <a class="button button-secondary" href="{{ $editing ? route('computers.show', $computer) : route('computers.index') }}">Cancelar</a>
        <button id="computer-submit" class="button button-primary" type="submit">{{ $submitLabel }}</button>
    </footer>
</form>

@push('scripts')
<script>
(() => {
    const fileInput = document.getElementById('nfo_file');
    const status = document.getElementById('nfo-status');
    const form = document.getElementById('computer-form');
    const submit = document.getElementById('computer-submit');

    fileInput?.addEventListener('change', async () => {
        const file = fileInput.files?.[0];
        if (!file) {
            status.textContent = 'No se ha seleccionado un archivo.';
            status.className = 'nfo-status';
            return;
        }
        if (!file.name.toLowerCase().endsWith('.nfo')) {
            fileInput.value = '';
            status.textContent = 'Selecciona un archivo con extension .nfo.';
            status.className = 'nfo-status error';
            return;
        }

        status.textContent = 'Analizando especificaciones...';
        status.className = 'nfo-status loading';
        const payload = new FormData();
        payload.append('nfo_file', file);

        try {
            const response = await fetch(@json(route('computers.nfo.parse')), {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json'},
                body: payload,
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'No se pudo analizar el archivo.');

            let filled = 0;
            Object.entries(data.specifications || {}).forEach(([field, value]) => {
                const input = form.elements.namedItem(field);
                if (input && !String(input.value || '').trim()) {
                    input.value = value;
                    input.dispatchEvent(new Event('change', {bubbles: true}));
                    filled++;
                }
            });
            status.textContent = filled > 0
                ? `NFO listo: se completaron ${filled} campos. Revisa los datos antes de guardar.`
                : 'El NFO fue leido, pero los campos ya tenian datos o no contenia valores reconocibles.';
            status.className = filled > 0 ? 'nfo-status success' : 'nfo-status warning';
        } catch (error) {
            status.textContent = error.message;
            status.className = 'nfo-status error';
        }
    });

    form?.addEventListener('submit', () => {
        submit.disabled = true;
        submit.textContent = 'Guardando...';
    });
})();
</script>
@endpush
