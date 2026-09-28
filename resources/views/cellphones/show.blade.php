@extends('layouts.app')
@section('title', $cellphone->model ?: 'Celular')
@section('page-title', 'Detalle de celular')

@section('content')
<div class="detail-toolbar">
    <a class="back-link" href="{{ route('cellphones.index') }}"><i class="bi bi-arrow-left"></i> Volver a celulares</a>
    <div class="page-actions">
        @can('update', $cellphone)<button class="button button-primary" type="button" data-open-cellphone-assignment><i class="bi bi-person-plus"></i> Nueva asignación</button>@endcan
        @can('shareSensitive', $cellphone)<a class="button button-whatsapp" target="_blank" rel="noopener" href="{{ route('cellphones.share', $cellphone) }}"><i class="bi bi-whatsapp"></i> Compartir</a>@endcan
        @can('update', $cellphone)<a class="button button-primary" href="{{ route('cellphones.edit', $cellphone) }}"><i class="bi bi-pencil"></i> Editar celular</a>@endcan
        @can('delete', $cellphone)<form method="POST" action="{{ route('cellphones.destroy', $cellphone) }}" onsubmit="return confirm('¿Eliminar este celular?');">@csrf @method('DELETE')<button class="button button-danger"><i class="bi bi-trash3"></i> Eliminar</button></form>@endcan
    </div>
</div>

<section class="asset-header mobile-asset-header">
    <div class="asset-icon"><i class="bi bi-phone"></i></div>
    <div class="asset-heading">
        <p class="eyebrow">Perfil del activo movil</p>
        <h2>{{ $cellphone->model ?: 'Celular sin modelo' }}</h2>
        <p>{{ $cellphone->assigned_name ?: 'Sin empleado asignado' }} · {{ $cellphone->phone_number ?: 'Sin linea registrada' }}</p>
        <div class="asset-meta"><span><i class="bi bi-building"></i>{{ $cellphone->area ?: 'Sin area' }}</span><span><i class="bi bi-clock-history"></i>{{ $cellphone->updated_at?->format('d/m/Y H:i') }}</span></div>
    </div>
    <span class="status large">{{ $cellphone->status }}</span>
</section>

<section class="detail-grid">
@foreach([
    'Empleado' => $cellphone->assigned_name,
    'Area' => $cellphone->area,
    'Telefono' => $cellphone->phone_number,
    'Correo' => $cellphone->email_account,
    'Cuenta de recuperacion' => $cellphone->recovery_account,
    'Fecha de recuperacion' => $cellphone->birth_date,
    'Bloqueo de aplicaciones' => $cellphone->has_app_lock ? 'Configurado' : 'No configurado',
    'Ultima actualizacion' => $cellphone->updated_at?->format('d/m/Y H:i'),
] as $label => $value)<article><span>{{ $label }}</span><strong>{{ $value ?: '-' }}</strong></article>@endforeach
</section>

<section class="panel operational-panel" id="cellphone-assignments">
    <div class="panel-header">
        <div><p class="eyebrow">Custodia del celular</p><h2>Historial de asignaciones</h2><p>Cada cambio conserva al usuario anterior y la fecha de reasignación.</p></div>
        <strong>{{ $cellphone->assignments->count() }}</strong>
    </div>
    @can('update', $cellphone)
        <details id="new-cellphone-assignment" class="operation-form-disclosure" @if($errors->any()) open @endif>
            <summary><i class="bi bi-person-plus"></i> Nueva asignación</summary>
            <form method="POST" action="{{ route('cellphones.assignments.store', $cellphone) }}" class="operation-form">
                @csrf
                <label><span>Nuevo empleado *</span><select name="employee_id" required><option value="">Seleccionar</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string)old('employee_id') === (string)$employee->id) @disabled((int)$cellphone->employee_id === (int)$employee->id)>{{ $employee->full_name }}{{ $employee->department ? ' · '.$employee->department : '' }}{{ (int)$cellphone->employee_id === (int)$employee->id ? ' (actual)' : '' }}</option>@endforeach</select></label>
                <label><span>Fecha de asignación *</span><input type="date" name="date_assigned" value="{{ old('date_assigned', now()->format('Y-m-d')) }}" required></label>
                <label><span>Condición</span><select name="condition_on_assign"><option>Bueno</option><option>Nuevo</option><option>Usado</option><option>Con detalles</option></select></label>
                <label><span>Área</span><input name="department" value="{{ old('department') }}" maxlength="100" placeholder="Se toma del empleado si se deja vacío"></label>
                <label class="wide"><span>Notas</span><textarea name="notes" maxlength="2000" placeholder="Accesorios entregados, estado o cualquier observación">{{ old('notes') }}</textarea></label>
                <button class="button button-primary" type="submit"><i class="bi bi-check2-circle"></i> Guardar nueva asignación</button>
            </form>
        </details>
    @endcan
    <div class="operation-timeline">
        @forelse($cellphone->assignments as $assignment)
            <article class="operation-record {{ $assignment->date_returned ? 'closed' : 'active' }}">
                <span class="operation-icon"><i class="bi {{ $assignment->date_returned ? 'bi-clock-history' : 'bi-person-check' }}"></i></span>
                <div class="operation-main">
                    <header><div><strong>{{ $assignment->assigned_to ?: 'Usuario no identificado' }}</strong><small>{{ $assignment->department ?: 'Sin área' }} · Asignado el {{ $assignment->date_assigned->format('d/m/Y') }}</small></div><span class="status">{{ $assignment->date_returned ? 'Anterior' : 'Actual' }}</span></header>
                    @if($assignment->notes)<p>{{ $assignment->notes }}</p>@endif
                    @if($assignment->date_returned)<small>Finalizó el {{ $assignment->date_returned->format('d/m/Y') }} · {{ $assignment->condition_on_return ?: 'Reasignado' }}</small>@endif
                </div>
            </article>
        @empty
            <p class="empty">Aún no hay movimientos registrados. La primera reasignación conservará automáticamente al usuario actual.</p>
        @endforelse
    </div>
</section>

@can('viewSensitive', $cellphone)
<section class="panel sensitive-panel">
    <div class="panel-header"><div><p class="eyebrow">Acceso restringido</p><h2>Credenciales configuradas</h2></div><span class="security-label"><i class="bi bi-shield-lock"></i> Solo personal autorizado</span></div>
    <div class="mobile-security-layout">
        <div class="detail-grid security-grid">
            @foreach(['Contrasena' => ['value' => $cellphone->password, 'field' => 'password'], 'Contrasena actualizada' => ['value' => $cellphone->updated_password, 'field' => 'updated_password'], 'PIN / clave de aplicaciones' => ['value' => $cellphone->app_lock_password, 'field' => 'app_lock_password'], 'Respuesta de seguridad' => ['value' => $cellphone->app_lock_answer, 'field' => 'app_lock_answer']] as $label => $secret)
                <article><span>{{ $label }}</span><strong class="secret-value" data-secret-url="{{ route('cellphones.secrets', $cellphone) }}" data-secret-field="{{ $secret['field'] }}">{{ $secret['value'] ? '••••••••' : '-' }}</strong>@if($secret['value'])<button class="reveal-secret" type="button"><i class="bi bi-eye"></i> Mostrar</button>@endif</article>
            @endforeach
        </div>
        <article class="pattern-detail-card">
            <div><span>Patron de desbloqueo</span><strong>{{ $cellphone->app_lock_pattern ? 'Configurado' : 'Sin configurar' }}</strong><small>{{ $cellphone->app_lock_pattern ? 'Secuencia visual registrada' : 'No hay un patron guardado' }}</small></div>
            @if($cellphone->app_lock_pattern)
                <div class="pattern-lock pattern-lock-view" data-pattern-view="{{ $cellphone->app_lock_pattern }}">
                    <svg viewBox="0 0 240 240" aria-hidden="true"><polyline points=""></polyline></svg>
                    <div class="pattern-lock-grid">@for($node = 1; $node <= 9; $node++)<span class="pattern-node" data-node="{{ $node }}"><span>{{ $node }}</span></span>@endfor</div>
                </div>
            @else
                <div class="pattern-empty"><i class="bi bi-grid-3x3-gap"></i></div>
            @endif
        </article>
    </div>
</section>
@endcan

@if($cellphone->update_note || $cellphone->comments)<section class="panel"><div class="panel-header"><div><p class="eyebrow">Seguimiento</p><h2>Notas del registro</h2></div></div>@if($cellphone->update_note)<p><strong>Actualizacion:</strong> {{ $cellphone->update_note }}</p>@endif @if($cellphone->comments)<p><strong>Comentarios:</strong> {{ $cellphone->comments }}</p>@endif</section>@endif
@include('shared.files', ['asset' => $cellphone, 'assetType' => 'cellphone'])
@include('shared.audit')
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-pattern-view]').forEach((view) => {
    const nodes = [...new Set((String(view.dataset.patternView || '').match(/[1-9]/g) || []))];
    const point = (node) => { const index = Number(node) - 1; return `${40 + (index % 3) * 80},${40 + Math.floor(index / 3) * 80}`; };
    view.querySelector('polyline').setAttribute('points', nodes.map(point).join(' '));
    view.querySelectorAll('.pattern-node').forEach((dot) => dot.classList.toggle('active', nodes.includes(dot.dataset.node)));
});
document.querySelector('[data-open-cellphone-assignment]')?.addEventListener('click', () => {
    const disclosure = document.getElementById('new-cellphone-assignment');
    if (!disclosure) return;
    disclosure.open = true;
    disclosure.scrollIntoView({ behavior: 'smooth', block: 'center' });
    window.setTimeout(() => disclosure.querySelector('select[name="employee_id"]')?.focus(), 350);
});
document.querySelectorAll('.reveal-secret').forEach((button) => button.addEventListener('click', () => {
    const value = button.previousElementSibling;
    const hidden = value.textContent.includes('•');
    value.textContent = hidden ? value.dataset.secret : '••••••••';
    button.innerHTML = hidden ? '<i class="bi bi-eye-slash"></i> Ocultar' : '<i class="bi bi-eye"></i> Mostrar';
}));
</script>
@endpush

@push('scripts')
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
