@include('shared.validation')
<form class="asset-form" method="POST" action="{{ $managedUser->exists ? route('users.update', $managedUser) : route('users.store') }}">
    @csrf
    @if($managedUser->exists)@method('PUT')@endif
    <section class="form-section">
        <div class="section-heading"><div><p class="eyebrow">Identidad</p><h2>Datos de la cuenta</h2></div><span class="section-number">01</span></div>
        <div class="form-grid three-columns">
            <label><span>Nombre completo *</span><input name="full_name" required maxlength="100" autocomplete="name" value="{{ old('full_name', $managedUser->full_name) }}"></label>
            <label><span>Nombre de usuario *</span><input name="username" required maxlength="50" autocomplete="username" spellcheck="false" value="{{ old('username', $managedUser->username) }}"></label>
            <label><span>Rol de acceso *</span><select name="role" required>@foreach(['admin'=>'Administrador','soporte'=>'Soporte','consulta'=>'Consulta'] as $value=>$label)<option value="{{ $value }}" @selected(old('role', $managedUser->role)===$value)>{{ $label }}</option>@endforeach</select></label>
        </div>
        <div class="role-guide">
            <article><i class="bi bi-shield-lock"></i><div><strong>Administrador</strong><small>Control total, usuarios, eliminaciones y auditoria.</small></div></article>
            <article><i class="bi bi-tools"></i><div><strong>Soporte</strong><small>Crea y edita registros operativos, sin administrar usuarios.</small></div></article>
            <article><i class="bi bi-eye"></i><div><strong>Consulta</strong><small>Acceso de solo lectura al inventario.</small></div></article>
        </div>
    </section>
    <section class="form-section">
        <div class="section-heading"><div><p class="eyebrow">Seguridad</p><h2>{{ $managedUser->exists ? 'Cambiar contrasena' : 'Contrasena inicial' }}</h2></div><span class="section-number">02</span></div>
        @if($managedUser->exists)<p class="form-hint"><i class="bi bi-info-circle"></i> Deja ambos campos vacios para conservar la contrasena actual.</p>@endif
        <div class="form-grid two-columns">
            <label><span>Contrasena {{ $managedUser->exists ? '' : '*' }}</span><input type="password" name="password" minlength="8" autocomplete="new-password" @required(!$managedUser->exists)></label>
            <label><span>Confirmar contrasena {{ $managedUser->exists ? '' : '*' }}</span><input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" @required(!$managedUser->exists)></label>
        </div>
        <label class="textarea-field"><span>Comentarios administrativos</span><textarea name="comments" maxlength="1000" placeholder="Motivo de acceso, area o referencia interna...">{{ old('comments', $managedUser->comments) }}</textarea></label>
    </section>
    <footer class="form-footer"><a class="button button-secondary" href="{{ $managedUser->exists ? route('users.show', $managedUser) : route('users.index') }}">Cancelar</a><button class="button button-primary"><i class="bi bi-check2-circle" aria-hidden="true"></i> {{ $managedUser->exists ? 'Guardar cambios' : 'Crear usuario' }}</button></footer>
</form>
