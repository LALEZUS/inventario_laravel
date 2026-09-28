@php
    $assignmentId = $assignmentId ?? null;
    $compact = $compact ?? false;
@endphp

<form class="responsiva-form {{ $compact ? 'is-compact' : '' }}" method="POST"
      action="{{ route('computers.responsiva', $computer) }}"
      data-preview-action="{{ route('computers.responsiva.preview', $computer) }}">
    @csrf
    @if($assignmentId)<input type="hidden" name="assignment_id" value="{{ $assignmentId }}">@endif
    <div class="responsiva-options">
        <label>
            <span>Formato</span>
            <select name="letterhead">
                <option value="1">Membretada</option>
                <option value="0">Sin membrete</option>
            </select>
        </label>
        <label>
            <span>Acomodo de fotos</span>
            <select name="photo_layout">
                <option value="balanced">2 fotos grandes por hoja</option>
                <option value="large">1 foto por hoja - tamaño máximo</option>
            </select>
        </label>
    </div>
    <div class="responsiva-actions">
        <button class="button button-secondary" type="button" data-responsiva-preview>
            <i class="bi bi-eye" aria-hidden="true"></i> Vista previa
        </button>
        <button class="button button-primary" type="submit">
            <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i> Generar PDF
        </button>
    </div>
</form>
