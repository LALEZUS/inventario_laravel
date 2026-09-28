@if ($errors->any())
    <div class="validation-summary" role="alert">
        <strong>Revisa los datos marcados:</strong>
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
