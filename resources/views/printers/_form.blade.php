@include('shared.validation')
@php
    $selectedInks = array_map('strval', old('linked_inks', $printer->linkedInkIds()));
    $selectedToners = array_map('strval', old('linked_toner', $printer->linkedTonerIds()));
@endphp
<form class="asset-form" method="POST" action="{{ $printer->exists ? route('printers.update', $printer) : route('printers.store') }}">
    @csrf
    @if($printer->exists) @method('PUT') @endif
    <section class="form-section">
        <div class="section-heading"><div><p class="eyebrow">Identificacion</p><h2>Datos de la impresora</h2></div><span class="section-number">01</span></div>
        <div class="form-grid three-columns">
            <label><span>Nombre *</span><input name="name" required maxlength="100" value="{{ old('name', $printer->name) }}"></label>
            <label><span>Marca</span><input name="brand" maxlength="100" value="{{ old('brand', $printer->brand) }}"></label>
            <label><span>Modelo</span><input name="model" maxlength="100" value="{{ old('model', $printer->model) }}"></label>
            <label><span>Serie</span><input name="serial" maxlength="100" value="{{ old('serial', $printer->serial) }}"></label>
            <label><span>Folio</span><input name="code" maxlength="50" value="{{ old('code', $printer->code) }}"></label>
            <label><span>Estado *</span><select name="status" required>@foreach(['Activo','Disponible','En servicio','Mantenimiento','Baja'] as $option)<option value="{{ $option }}" @selected(old('status', $printer->status) === $option)>{{ $option }}</option>@endforeach</select></label>
        </div>
    </section>

    <section class="form-section">
        <div class="section-heading"><div><p class="eyebrow">Operacion</p><h2>Red y asignacion</h2></div><span class="section-number">02</span></div>
        <div class="form-grid three-columns">
            <label class="switch-field"><span>Impresora en red</span><input type="checkbox" name="is_network" value="1" @checked(old('is_network', $printer->is_network))></label>
            <label><span>Direccion IP</span><input name="ip_address" maxlength="45" value="{{ old('ip_address', $printer->ip_address) }}" placeholder="192.168.15.100"></label>
            <label><span>Zona o ubicacion</span><input name="zone" maxlength="100" value="{{ old('zone', $printer->zone) }}"></label>
            <label><span>Empleado registrado</span><select name="employee_id"><option value="">Sin vincular</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected((string) old('employee_id', $printer->employee_id) === (string) $employee->id)>{{ $employee->full_name }}</option>@endforeach</select></label>
            <label><span>Responsable manual</span><input name="assigned_to" maxlength="150" value="{{ old('assigned_to', $printer->assigned_to) }}"></label>
        </div>
    </section>

    <section class="form-section">
        <div class="section-heading"><div><p class="eyebrow">Consumibles</p><h2>Tintas y toner compatibles</h2></div><span class="section-number">03</span></div>
        <div class="form-grid three-columns">
            <label><span>Tipo de suministro</span><select name="supply_type"><option value="">Sin definir</option>@foreach(['Tinta','Tóner','Mixto','Otro'] as $option)<option value="{{ $option }}" @selected(old('supply_type', $printer->supply_type) === $option)>{{ $option }}</option>@endforeach</select></label>
            <label><span>Referencia del consumible</span><input name="ink_type" maxlength="100" value="{{ old('ink_type', $printer->ink_type) }}" placeholder="Ej. 544 BK, C, Y, M"></label>
        </div>
        <p class="field-help">Selecciona los consumibles que utiliza este equipo. Las existencias se consultan desde su ficha.</p>
        <p class="eyebrow">Tintas</p>
        <div class="check-grid supply-check-grid">@forelse($inks as $ink)<label><input type="checkbox" name="linked_inks[]" value="{{ $ink->id }}" @checked(in_array((string) $ink->id, $selectedInks, true))><span>{{ $ink->brand }} {{ $ink->type }} {{ $ink->color }} ({{ $ink->quantity }})</span></label>@empty<p class="empty">No hay tintas registradas.</p>@endforelse</div>
        <p class="eyebrow supply-subheading">Toner</p>
        <div class="check-grid supply-check-grid">@forelse($toners as $toner)<label><input type="checkbox" name="linked_toner[]" value="{{ $toner->id }}" @checked(in_array((string) $toner->id, $selectedToners, true))><span>{{ $toner->brand }} {{ $toner->model }} ({{ $toner->quantity }})</span></label>@empty<p class="empty">No hay toner registrado.</p>@endforelse</div>
        <label class="textarea-field"><span>Comentarios</span><textarea name="comments">{{ old('comments', $printer->comments) }}</textarea></label>
    </section>

    <footer class="form-footer"><a class="button button-secondary" href="{{ $printer->exists ? route('printers.show', $printer) : route('printers.index') }}">Cancelar</a><button class="button button-primary">{{ $printer->exists ? 'Guardar cambios' : 'Registrar impresora' }}</button></footer>
</form>
