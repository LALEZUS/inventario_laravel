@include('shared.validation')
<form class="asset-form" method="POST" action="{{ $cellphone->exists ? route('cellphones.update',$cellphone) : route('cellphones.store') }}">
    @csrf @if($cellphone->exists) @method('PUT') @endif
    <section class="form-section"><div class="section-heading"><div><p class="eyebrow">Asignacion</p><h2>Equipo y empleado</h2></div><span class="section-number">01</span></div>
        <div class="form-grid three-columns">
            @if($cellphone->exists)
                <label><span>Empleado actual</span><input value="{{ $cellphone->assigned_name ?: 'Sin empleado asignado' }}" readonly><input type="hidden" name="employee_id" value="{{ $cellphone->employee_id }}"><small>Para cambiarlo y conservar el historial, usa “Nueva asignación” en el detalle del celular.</small></label>
            @else
                <label><span>Empleado registrado</span><select name="employee_id"><option value="">Sin vincular</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(old('employee_id',$cellphone->employee_id)==$employee->id)>{{ $employee->full_name }}{{ $employee->department ? ' · '.$employee->department : '' }}</option>@endforeach</select></label>
            @endif
            <label><span>Nombre manual</span><input name="employee_name_legacy" maxlength="150" value="{{ old('employee_name_legacy',$cellphone->employee_name_legacy) }}"></label>
            <label><span>Modelo *</span><input name="model" maxlength="100" required value="{{ old('model',$cellphone->model) }}"></label>
            <label><span>Area</span><input name="area" maxlength="100" value="{{ old('area',$cellphone->area) }}"></label>
            <label><span>Telefono</span><input name="phone_number" maxlength="20" value="{{ old('phone_number',$cellphone->phone_number) }}"></label>
            <label><span>Estado</span><select name="status">@foreach(['En Uso','Disponible','Mantenimiento','Baja'] as $status)<option @selected(old('status',$cellphone->status)===$status)>{{ $status }}</option>@endforeach</select></label>
        </div>
    </section>
    <section class="form-section"><div class="section-heading"><div><p class="eyebrow">Cuentas</p><h2>Correo y recuperacion</h2></div><span class="section-number">02</span></div>
        <div class="form-grid three-columns"><label><span>Correo del equipo</span><input name="email_account" maxlength="100" value="{{ old('email_account',$cellphone->email_account) }}"></label><label><span>Cuenta de recuperacion</span><input name="recovery_account" maxlength="100" value="{{ old('recovery_account',$cellphone->recovery_account) }}"></label><label><span>Fecha de nacimiento / recuperacion</span><input name="birth_date" maxlength="50" value="{{ old('birth_date',$cellphone->birth_date) }}"></label></div>
    </section>
    <section class="form-section secret-section"><div class="section-heading"><div><p class="eyebrow">Acceso restringido</p><h2>Credenciales y bloqueo</h2></div><span class="section-number">03</span></div>
        @if($cellphone->exists)<p class="field-help">Deja una credencial vacia para conservar su valor actual.</p>@endif
        <div class="form-grid three-columns"><label><span>Contrasena</span><input type="password" name="password" maxlength="100" autocomplete="new-password"></label><label><span>Contrasena actualizada</span><input type="password" name="updated_password" maxlength="100" autocomplete="new-password"></label><label class="switch-field"><span>Bloqueo de aplicaciones</span><input type="hidden" name="has_app_lock" value="0"><input type="checkbox" name="has_app_lock" value="1" @checked(old('has_app_lock',$cellphone->has_app_lock))></label><label><span>PIN / clave de aplicaciones</span><input type="password" name="app_lock_password" maxlength="100" autocomplete="new-password"></label><label><span>Respuesta de seguridad</span><input type="password" name="app_lock_answer" maxlength="255" autocomplete="new-password"></label></div>
        <div class="pattern-field" data-pattern-editor>
            <div class="pattern-field-copy"><span>Patron de desbloqueo</span><strong>Dibuja la secuencia sobre los puntos</strong><small>Tambien puedes seleccionar los puntos uno por uno.</small></div>
            <div class="pattern-lock" aria-label="Selector de patron de desbloqueo">
                <svg viewBox="0 0 240 240" aria-hidden="true"><polyline points=""></polyline></svg>
                <div class="pattern-lock-grid">
                    @for($node = 1; $node <= 9; $node++)<button type="button" class="pattern-node" data-node="{{ $node }}" aria-label="Punto {{ $node }}"><span>{{ $node }}</span></button>@endfor
                </div>
            </div>
            <div class="pattern-field-actions">
                <input type="hidden" name="app_lock_pattern" value="{{ old('app_lock_pattern',$cellphone->app_lock_pattern) }}">
                <span class="pattern-sequence" aria-live="polite">Sin patron configurado</span>
                <button class="pattern-clear" type="button"><i class="bi bi-arrow-counterclockwise"></i> Limpiar</button>
            </div>
        </div>
    </section>
    <section class="form-section"><div class="section-heading"><div><p class="eyebrow">Seguimiento</p><h2>Notas</h2></div><span class="section-number">04</span></div><label class="textarea-field"><span>Nota de actualizacion</span><textarea name="update_note">{{ old('update_note',$cellphone->update_note) }}</textarea></label><label class="textarea-field"><span>Comentarios</span><textarea name="comments">{{ old('comments',$cellphone->comments) }}</textarea></label></section>
    <footer class="form-footer"><a class="button button-secondary" href="{{ $cellphone->exists ? route('cellphones.show',$cellphone) : route('cellphones.index') }}">Cancelar</a><button class="button button-primary">{{ $cellphone->exists ? 'Guardar cambios' : 'Registrar celular' }}</button></footer>
</form>
@once
@push('scripts')
<script>
(() => {
    const parsePattern = (value) => [...new Set((String(value || '').match(/[1-9]/g) || []))];
    const point = (node) => {
        const index = Number(node) - 1;
        return `${40 + (index % 3) * 80},${40 + Math.floor(index / 3) * 80}`;
    };
    const paint = (root, nodes) => {
        root.querySelector('polyline').setAttribute('points', nodes.map(point).join(' '));
        root.querySelectorAll('.pattern-node').forEach((dot) => dot.classList.toggle('active', nodes.includes(dot.dataset.node)));
    };

    document.querySelectorAll('[data-pattern-editor]').forEach((editor) => {
        const lock = editor.querySelector('.pattern-lock');
        const input = editor.querySelector('input[name="app_lock_pattern"]');
        const summary = editor.querySelector('.pattern-sequence');
        const enabledCheckbox = editor.closest('form')?.querySelector('input[type="checkbox"][name="has_app_lock"]');
        let nodes = parsePattern(input.value);
        let drawing = false;
        let suppressClick = false;
        const render = () => {
            input.value = nodes.join('-');
            if (nodes.length && enabledCheckbox) enabledCheckbox.checked = true;
            summary.textContent = nodes.length ? `Secuencia: ${nodes.join(' → ')}` : 'Sin patron configurado';
            paint(lock, nodes);
        };
        const add = (node) => {
            if (!node || nodes.includes(node)) return;
            nodes.push(node);
            render();
        };
        lock.querySelectorAll('.pattern-node').forEach((dot) => dot.addEventListener('click', () => {
            if (suppressClick) { suppressClick = false; return; }
            const existing = nodes.indexOf(dot.dataset.node);
            if (existing >= 0) nodes = nodes.slice(0, existing);
            else nodes.push(dot.dataset.node);
            render();
        }));
        lock.addEventListener('pointerdown', (event) => {
            const dot = event.target.closest('.pattern-node');
            if (!dot) return;
            event.preventDefault();
            drawing = true;
            suppressClick = true;
            add(dot.dataset.node);
            lock.setPointerCapture?.(event.pointerId);
        });
        lock.addEventListener('pointermove', (event) => {
            if (!drawing) return;
            const dot = document.elementFromPoint(event.clientX, event.clientY)?.closest('.pattern-node');
            if (dot && lock.contains(dot)) add(dot.dataset.node);
        });
        ['pointerup', 'pointercancel', 'pointerleave'].forEach((name) => lock.addEventListener(name, () => { drawing = false; }));
        editor.querySelector('.pattern-clear').addEventListener('click', () => { nodes = []; render(); });
        render();
    });

    document.querySelectorAll('[data-pattern-view]').forEach((view) => paint(view, parsePattern(view.dataset.patternView)));
})();
</script>
@endpush
@endonce
