@extends('layouts.app')

@section('title', $galleryItem->title)
@section('page-title', 'Detalle de imagen')

@section('content')
    <div class="detail-toolbar">
        <a class="back-link" href="{{ route('gallery.index') }}">&larr; Volver</a>
        @can('update', $galleryItem)
            <a class="button button-primary" href="{{ route('gallery.edit', $galleryItem) }}">Editar</a>
        @endcan
    </div>

    <section class="panel gallery-detail">
        <button class="gallery-image-trigger" type="button" data-gallery-open aria-label="Abrir imagen ampliada">
            <img src="{{ route('gallery.image', $galleryItem) }}" alt="{{ $galleryItem->title }}" data-gallery-source draggable="false">
            <span class="gallery-image-overlay"><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i> Ver en grande</span>
        </button>
        <h2>{{ $galleryItem->title }}</h2>
        <p>{{ $galleryItem->notes }}</p>
    </section>

    <div class="gallery-lightbox" data-gallery-lightbox hidden role="dialog" aria-modal="true" aria-labelledby="gallery-lightbox-title">
        <div class="gallery-lightbox-backdrop" data-gallery-close></div>
        <div class="gallery-lightbox-frame">
            <header class="gallery-lightbox-header">
                <div>
                    <p class="eyebrow">Vista ampliada</p>
                    <h2 id="gallery-lightbox-title">{{ $galleryItem->title }}</h2>
                </div>
                <button class="icon-button" type="button" data-gallery-close aria-label="Cerrar visor" title="Cerrar">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>
            <div class="gallery-lightbox-stage" data-gallery-stage tabindex="0" aria-label="Area de visualizacion. Usa la rueda para ampliar y arrastra para mover.">
                <img src="{{ route('gallery.image', $galleryItem) }}" alt="{{ $galleryItem->title }}" data-gallery-image draggable="false">
            </div>
            <footer class="gallery-lightbox-controls">
                <div class="gallery-lightbox-help">Rueda para ampliar · arrastra para mover</div>
                <div class="gallery-lightbox-actions">
                    <button class="icon-button" type="button" data-gallery-zoom-out aria-label="Alejar" title="Alejar (-)"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>
                    <span class="gallery-zoom-label" data-gallery-zoom-label>100%</span>
                    <button class="icon-button" type="button" data-gallery-zoom-in aria-label="Acercar" title="Acercar (+)"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                    <button class="icon-button" type="button" data-gallery-reset aria-label="Restablecer zoom" title="Restablecer (0)"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></button>
                    <button class="icon-button" type="button" data-gallery-fullscreen aria-label="Pantalla completa" title="Pantalla completa"><i class="bi bi-fullscreen" aria-hidden="true"></i></button>
                </div>
            </footer>
        </div>
    </div>

    @include('shared.audit')
@endsection

@push('scripts')
<script>
(() => {
    const lightbox = document.querySelector('[data-gallery-lightbox]');
    const trigger = document.querySelector('[data-gallery-open]');
    if (!lightbox || !trigger) return;
    const stage = lightbox.querySelector('[data-gallery-stage]');
    const image = lightbox.querySelector('[data-gallery-image]');
    const zoomLabel = lightbox.querySelector('[data-gallery-zoom-label]');
    const frame = lightbox.querySelector('.gallery-lightbox-frame');
    let zoom = 1, offsetX = 0, offsetY = 0, dragging = false, pointerId = null, lastX = 0, lastY = 0;
    const render = () => {
        image.style.transform = `translate(${offsetX}px, ${offsetY}px) scale(${zoom})`;
        image.classList.toggle('is-zoomed', zoom > 1);
        zoomLabel.textContent = `${Math.round(zoom * 100)}%`;
    };
    const reset = () => { zoom = 1; offsetX = 0; offsetY = 0; render(); };
    const changeZoom = (amount) => {
        zoom = Math.min(4, Math.max(1, zoom + amount));
        if (zoom === 1) { offsetX = 0; offsetY = 0; }
        render();
    };
    const open = () => {
        lightbox.hidden = false;
        document.body.classList.add('gallery-lightbox-open');
        reset();
        requestAnimationFrame(() => lightbox.querySelector('[data-gallery-close]').focus());
    };
    const close = () => {
        if (lightbox.hidden) return;
        lightbox.hidden = true;
        document.body.classList.remove('gallery-lightbox-open');
        trigger.focus();
    };
    trigger.addEventListener('click', open);
    lightbox.querySelectorAll('[data-gallery-close]').forEach((button) => button.addEventListener('click', close));
    lightbox.querySelector('[data-gallery-zoom-in]').addEventListener('click', () => changeZoom(.25));
    lightbox.querySelector('[data-gallery-zoom-out]').addEventListener('click', () => changeZoom(-.25));
    lightbox.querySelector('[data-gallery-reset]').addEventListener('click', reset);
    lightbox.querySelector('[data-gallery-fullscreen]').addEventListener('click', () => {
        if (document.fullscreenElement) document.exitFullscreen?.();
        else frame.requestFullscreen?.();
    });
    stage.addEventListener('wheel', (event) => {
        event.preventDefault();
        changeZoom(event.deltaY < 0 ? .25 : -.25);
    }, { passive: false });
    stage.addEventListener('dblclick', () => zoom > 1 ? reset() : changeZoom(1));
    stage.addEventListener('pointerdown', (event) => {
        if (zoom === 1) return;
        dragging = true; pointerId = event.pointerId; lastX = event.clientX; lastY = event.clientY;
        image.classList.add('is-dragging'); stage.setPointerCapture(pointerId);
    });
    stage.addEventListener('pointermove', (event) => {
        if (!dragging || event.pointerId !== pointerId) return;
        offsetX += event.clientX - lastX; offsetY += event.clientY - lastY;
        lastX = event.clientX; lastY = event.clientY; render();
    });
    const stopDragging = (event) => {
        if (event.pointerId !== pointerId) return;
        dragging = false; image.classList.remove('is-dragging'); stage.releasePointerCapture?.(pointerId); pointerId = null;
    };
    stage.addEventListener('pointerup', stopDragging);
    stage.addEventListener('pointercancel', stopDragging);
    document.addEventListener('keydown', (event) => {
        if (lightbox.hidden) return;
        if (event.key === 'Escape') close();
        if (event.key === '+' || event.key === '=') changeZoom(.25);
        if (event.key === '-') changeZoom(-.25);
        if (event.key === '0') reset();
    });
})();
</script>
@endpush
