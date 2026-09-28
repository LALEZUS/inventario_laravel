@props(['tabs', 'active', 'label' => 'Secciones del modulo'])
<nav class="module-tabs" aria-label="{{ $label }}">
    @foreach($tabs as $key => $tab)
        <a href="{{ $tab['url'] }}" class="{{ $active === $key ? 'active' : '' }}" @if($active === $key) aria-current="page" @endif>
            <span class="module-tab-icon"><i class="bi {{ $tab['icon'] }}" aria-hidden="true"></i></span>
            <span class="module-tab-copy">
                <strong>{{ $tab['label'] }}</strong>
                <small>{{ $tab['description'] }}</small>
            </span>
            <b>{{ $tab['count'] }}</b>
        </a>
    @endforeach
</nav>
