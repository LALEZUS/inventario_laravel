@include('shared.validation')
@php
    $accountStatuses = config('inventory.catalogs.account_statuses');
    $selectedStatus = old('status', $accountCredential->status ?: 'Activo');
    if ($selectedStatus && ! in_array($selectedStatus, $accountStatuses, true)) $accountStatuses[] = $selectedStatus;
@endphp
<form class="asset-form" method="POST" action="{{ $accountCredential->exists ? route('account-credentials.update',$accountCredential) : route('account-credentials.store') }}">
@csrf @if($accountCredential->exists)@method('PUT')@endif
<section class="form-section secret-section"><div class="section-heading"><div><p class="eyebrow">Cuenta corporativa</p><h2>Datos de acceso</h2></div><span class="security-label">Dato protegido</span></div>
<div class="form-grid three-columns">
<label><span>Correo *</span><input type="email" name="email" required maxlength="150" value="{{ old('email',$accountCredential->email) }}"></label>
<label><span>Tipo *</span><select name="account_type" required>@foreach(['Microsoft 365','Gmail','Hospedaje','Personal'] as $option)<option value="{{ $option }}" @selected(old('account_type',$accountCredential->account_type) === $option)>{{ $option }}</option>@endforeach</select></label>
<label><span>Estado *</span><select name="status" required>@foreach($accountStatuses as $option)<option value="{{ $option }}" @selected($selectedStatus === $option)>{{ $option }}</option>@endforeach</select></label>
<label><span>Empleado</span><select name="employee_id"><option value="">Sin relacion</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string)old('employee_id',$accountCredential->employee_id) === (string)$employee->id)>{{ $employee->full_name }}{{ $employee->status !== 'Activo' ? ' ('.$employee->status.')' : '' }}</option>@endforeach</select></label>
<label><span>Responsable historico</span><input name="assigned_to" maxlength="150" value="{{ old('assigned_to',$accountCredential->assigned_to) }}"></label>
<label><span>Contrasena {{ $accountCredential->exists ? '(vacia conserva la actual)' : '*' }}</span><input type="password" name="password" {{ $accountCredential->exists ? '' : 'required' }} maxlength="255" autocomplete="new-password"></label>
</div><label class="textarea-field"><span>Comentarios</span><textarea name="comments">{{ old('comments',$accountCredential->comments) }}</textarea></label></section>
<footer class="form-footer"><a class="button button-secondary" href="{{ $accountCredential->exists ? route('account-credentials.show',$accountCredential) : route('credentials.index',['type'=>'accounts']) }}">Cancelar</a><button class="button button-primary">{{ $accountCredential->exists ? 'Guardar cambios' : 'Registrar cuenta' }}</button></footer>
</form>
