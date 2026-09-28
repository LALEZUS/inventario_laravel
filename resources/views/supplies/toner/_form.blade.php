@include('shared.validation')
<form class="asset-form supply-form" method="POST" action="{{ $toner->exists ? route('toner.update', $toner) : route('toner.store') }}">
    @csrf @if($toner->exists) @method('PUT') @endif
    <section class="form-section">
        <div class="section-heading"><div><p class="eyebrow">Identificacion</p><h2>Datos del toner</h2><p class="section-help">Registra el modelo y la cantidad para mantener el inventario actualizado.</p></div><span class="section-number">01</span></div>
        <div class="form-grid three-columns">
            <label><span>Marca *</span><input name="brand" required maxlength="255" value="{{ old('brand', $toner->brand) }}" placeholder="Ej. Brother"></label>
            <label><span>Modelo *</span><input name="model" required maxlength="255" value="{{ old('model', $toner->model) }}" placeholder="Ej. TN-1060"></label>
            <label><span>Existencia *</span><input type="number" name="quantity" min="0" max="100000" step="1" required value="{{ old('quantity', $toner->quantity ?? 0) }}"><small>Cantidad disponible actualmente.</small></label>
            <label><span>Umbral de alerta *</span><input type="number" name="low_stock_threshold" min="0" max="100000" step="1" required value="{{ old('low_stock_threshold', $toner->low_stock_threshold ?? 2) }}"><small>Se mostrará una alerta cuando llegue a este número.</small></label>
            <label><span>Estado *</span><select name="status" required>@foreach(['NUEVO','DISPONIBLE','BAJO','AGOTADO'] as $option)<option value="{{ $option }}" @selected(old('status', $toner->status) === $option)>{{ $option }}</option>@endforeach</select><small>Así aparecerá en el inventario.</small></label>
        </div>
        <label class="textarea-field"><span>Comentarios <small>(opcional)</small></span><textarea name="comments" placeholder="Agrega una nota útil sobre este toner, por ejemplo dónde está guardado.">{{ old('comments', $toner->notes) }}</textarea></label>
    </section>
    <footer class="form-footer"><a class="button button-secondary" href="{{ $toner->exists ? route('toner.show', $toner) : route('supplies.index', ['type' => 'toner']) }}">Cancelar</a><button class="button button-primary">{{ $toner->exists ? 'Guardar cambios' : 'Registrar toner' }}</button></footer>
</form>
