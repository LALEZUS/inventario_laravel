@extends('layouts.app')

@section('title', 'Gestion masiva de '.$set['title'])
@section('page-title', 'Gestion masiva')

@section('content')
    <section class="page-intro compact">
        <div>
            <p class="eyebrow">Acciones por lote</p>
            <h2>{{ $set['title'] }}</h2>
            <p>Selecciona hasta 500 registros y aplica un cambio controlado.</p>
        </div>
        <form class="search-form">
            <input name="search" value="{{ $search }}" placeholder="Buscar por nombre...">
            <button class="button button-secondary">Buscar</button>
        </form>
    </section>

    <form method="POST" action="{{ route('bulk.update', $set['key']) }}" class="panel bulk-panel">
        @csrf
        @method('PUT')
        <div class="bulk-toolbar">
            <label>
                <span>Accion</span>
                <select name="action" required>
                    <option value="">Seleccionar</option>
                    @if(isset($set['bulk']['status']))<option value="status">Cambiar estado</option>@endif
                    @if(isset($set['bulk']['location']))<option value="location">Cambiar ubicacion / area</option>@endif
                    @if(auth()->user()->role === 'admin')<option value="delete">Eliminar registros</option>@endif
                </select>
            </label>
            <label>
                <span>Nuevo valor</span>
                <input name="value" list="bulk-status-values" placeholder="Estado, ubicacion o area">
                <datalist id="bulk-status-values">
                    @foreach(($set['bulk']['status'] ?? []) as $status)<option value="{{ $status }}"></option>@endforeach
                </datalist>
            </label>
            <div class="bulk-actions">
                <button class="button button-primary" type="submit">Aplicar a seleccionados</button>
                <button class="button button-secondary" type="submit" formnovalidate formaction="{{ route('inventory-export.download', $set['key']) }}" name="export_selected" value="1"><i class="bi bi-file-earmark-excel"></i> Exportar seleccionados</button>
            </div>
        </div>

        <div class="bulk-selection-bar">
            <label class="bulk-check-all"><input type="checkbox" data-check-all aria-label="Seleccionar todos los registros visibles"><span>Seleccionar pagina</span></label>
            <span><strong data-selected-count>0</strong> seleccionados</span>
            @if(auth()->user()->role === 'admin')<button class="bulk-delete-button" type="submit" formnovalidate name="action" value="delete" data-delete-selected><i class="bi bi-trash3"></i> Eliminar seleccionados</button>@endif
        </div>

        <div class="table-scroll">
            <table>
                <thead><tr><th><input type="checkbox" data-check-all aria-label="Seleccionar todos"></th><th>Registro</th>@if(isset($set['columns']['status']))<th>Estado</th>@endif @if(isset($set['bulk']['location']))<th>Ubicacion / area</th>@endif</tr></thead>
                <tbody>
                    @forelse($records as $record)
                        @php($recordName = data_get($record, $set['display']) ?: 'Registro #'.$record->id)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $record->id }}" aria-label="Seleccionar {{ $recordName }}"></td>
                            <td><strong>{{ $recordName }}</strong><small>ID {{ $record->id }}</small></td>
                            @if(isset($set['columns']['status']))<td><span class="status">{{ $record->status }}</span></td>@endif
                            @if(isset($set['bulk']['location']))<td>{{ data_get($record, $set['bulk']['location']) ?: '-' }}</td>@endif
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No hay registros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($records->hasPages())<div class="pagination">{{ $records->links() }}</div>@endif
    </form>
@endsection

@push('scripts')
    <script>
        const bulkKey = 'inventory-bulk-{{ $set['key'] }}';
        const storedIds = new Set(JSON.parse(localStorage.getItem(bulkKey) || '[]').map(String));
        const bulkBoxes = [...document.querySelectorAll('input[name="ids[]"]')];
        const bulkMasters = [...document.querySelectorAll('[data-check-all]')];
        const bulkCount = document.querySelector('[data-selected-count]');
        bulkBoxes.forEach((checkbox) => { checkbox.checked = storedIds.has(checkbox.value); });
        const refreshBulkSelection = () => {
            bulkBoxes.forEach((checkbox) => checkbox.checked ? storedIds.add(checkbox.value) : storedIds.delete(checkbox.value));
            localStorage.setItem(bulkKey, JSON.stringify([...storedIds]));
            if (bulkCount) bulkCount.textContent = storedIds.size;
            bulkMasters.forEach((master) => {
                const visible = bulkBoxes.filter((checkbox) => checkbox.checked).length;
                master.checked = bulkBoxes.length > 0 && visible === bulkBoxes.length;
                master.indeterminate = visible > 0 && visible < bulkBoxes.length;
            });
        };
        bulkMasters.forEach((master) => master.addEventListener('change', (event) => {
            bulkBoxes.forEach((checkbox) => { checkbox.checked = event.target.checked; });
            refreshBulkSelection();
        }));
        bulkBoxes.forEach((checkbox) => checkbox.addEventListener('change', refreshBulkSelection));
        refreshBulkSelection();
        document.querySelector('.bulk-panel')?.addEventListener('submit', (event) => {
            if (!storedIds.size) { event.preventDefault(); alert('Selecciona al menos un registro.'); return; }
            if (event.submitter?.name === 'export_selected') {
                event.preventDefault();
                const exportForm = document.createElement('form');
                exportForm.method = 'POST';
                exportForm.action = @json(route('inventory-export.download', $set['key']));
                const csrf = document.createElement('input');
                csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = @json(csrf_token());
                exportForm.append(csrf);
                storedIds.forEach((id) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden'; hidden.name = 'ids[]'; hidden.value = id;
                    exportForm.append(hidden);
                });
                document.body.append(exportForm);
                HTMLFormElement.prototype.submit.call(exportForm);
                return;
            }
            document.querySelectorAll('.bulk-panel input[name="ids[]"]').forEach((input) => input.disabled = true);
            storedIds.forEach((id) => {
                const hidden = document.createElement('input');
                hidden.type = 'hidden'; hidden.name = 'ids[]'; hidden.value = id;
                event.currentTarget.append(hidden);
            });
            if (event.submitter?.value === 'delete' && !confirm('Esta accion eliminara los registros seleccionados. Continuar?')) event.preventDefault();
        });
    </script>
    {{-- legacy selection script retained only for old cached markup --}}
    <script>
        if (false) {
        document.querySelector('[data-check-all]')?.addEventListener('change', (event) => {
            document.querySelectorAll('input[name="ids[]"]').forEach((checkbox) => {
                checkbox.checked = event.target.checked;
            });
        });
        document.querySelector('.bulk-panel')?.addEventListener('submit', (event) => {
            if (!document.querySelector('input[name="ids[]"]:checked')) {
                event.preventDefault();
                alert('Selecciona al menos un registro.');
            } else if (event.target.elements.action.value === 'delete' && !confirm('Esta accion eliminara los registros seleccionados. ¿Continuar?')) {
                event.preventDefault();
            }
        });
        }
    </script>
    <script>
        if (false) {
        const bulkBoxes = [...document.querySelectorAll('input[name="ids[]"]')];
        const bulkMasters = [...document.querySelectorAll('[data-check-all]')];
        const bulkCount = document.querySelector('[data-selected-count]');
        const refreshBulkSelection = () => {
            const selected = bulkBoxes.filter((checkbox) => checkbox.checked).length;
            if (bulkCount) bulkCount.textContent = selected;
            bulkMasters.forEach((master) => {
                master.checked = bulkBoxes.length > 0 && selected === bulkBoxes.length;
                master.indeterminate = selected > 0 && selected < bulkBoxes.length;
            });
        };
        bulkMasters.forEach((master) => master.addEventListener('change', (event) => {
            bulkBoxes.forEach((checkbox) => { checkbox.checked = event.target.checked; });
            refreshBulkSelection();
        }));
        bulkBoxes.forEach((checkbox) => checkbox.addEventListener('change', refreshBulkSelection));
        refreshBulkSelection();
        document.querySelector('.bulk-panel')?.addEventListener('submit', (event) => {
            if (event.submitter?.value === 'delete' && !confirm('Esta accion eliminara los registros seleccionados. Continuar?')) event.preventDefault();
        });
        }
    </script>
@endpush
