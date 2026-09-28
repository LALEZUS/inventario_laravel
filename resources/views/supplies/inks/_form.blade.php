@include('shared.validation')
<form class="asset-form supply-form" method="POST" action="{{ $ink->exists ? route('inks.update', $ink) : route('inks.store') }}">
    @csrf @if($ink->exists) @method('PUT') @endif
    <section class="form-section">
        <div class="section-heading"><div><p class="eyebrow">Identificacion</p><h2>Datos de la tinta</h2><p class="section-help">Indica la marca, referencia y color para encontrarla fácilmente.</p></div><span class="section-number">01</span></div>
        <div class="form-grid three-columns">
            <label><span>Marca *</span><input name="brand" required maxlength="255" value="{{ old('brand', $ink->brand) }}" placeholder="Ej. Epson"></label>
            <label><span>Modelo</span><input name="model" maxlength="255" value="{{ old('model', $ink->model) }}" placeholder="Ej. EcoTank"></label>
            <label><span>Referencia o tipo *</span><input name="type" required maxlength="100" value="{{ old('type', $ink->type) }}" placeholder="Ej. 544"></label>
            <label><span>Color *</span><input name="color" list="ink-color-options" required maxlength="255" value="{{ old('color', $ink->color) }}" placeholder="Ej. BK - Negro"><small>Elige una sugerencia o escribe otro color.</small></label>
            <label><span>Capacidad</span><input name="capacity" maxlength="100" value="{{ old('capacity', $ink->capacity) }}" placeholder="Ej. 65 ml"></label>
            <label><span>Existencia *</span><input type="number" name="quantity" min="0" max="100000" step="1" required value="{{ old('quantity', $ink->quantity ?? 0) }}"><small>Cantidad disponible actualmente.</small></label>
            <label><span>Umbral de alerta *</span><input type="number" name="low_stock_threshold" min="0" max="100000" step="1" required value="{{ old('low_stock_threshold', $ink->low_stock_threshold ?? 2) }}"><small>Se mostrará una alerta cuando llegue a este número.</small></label>
            <label><span>Fecha de compra</span><input type="date" name="purchase_date" value="{{ old('purchase_date', $ink->dateValue('purchase_date')) }}"><small>Opcional.</small></label>
            <label><span>Fecha de vencimiento</span><input type="date" name="expiry_date" value="{{ old('expiry_date', $ink->dateValue('expiry_date')) }}"><small>Opcional.</small></label>
            <label><span>Estado *</span><select name="status" required>@foreach(['Disponible','Completo','Bajo','Agotado','Vencido'] as $option)<option value="{{ $option }}" @selected(old('status', $ink->status) === $option)>{{ $option }}</option>@endforeach</select><small>Así aparecerá en el inventario.</small></label>
        </div>
        <label class="textarea-field"><span>Comentarios <small>(opcional)</small></span><textarea name="comments" placeholder="Agrega una nota útil sobre esta tinta, por ejemplo dónde está guardada.">{{ old('comments', $ink->comments) }}</textarea></label>
    </section>
    <datalist id="ink-color-options">
        <option value="BK - Negro">
        <option value="C - Cyan">
        <option value="M - Magenta">
        <option value="Y - Yellow">
    </datalist>
    <footer class="form-footer"><a class="button button-secondary" href="{{ $ink->exists ? route('inks.show', $ink) : route('supplies.index', ['type' => 'inks']) }}">Cancelar</a><button class="button button-primary">{{ $ink->exists ? 'Guardar cambios' : 'Registrar tinta' }}</button></footer>
</form>
