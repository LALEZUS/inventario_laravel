<div class="theme-picker">
    <button class="theme-toggle" type="button" aria-haspopup="menu" aria-expanded="false" aria-label="Seleccionar tema">
        <i class="bi bi-palette" aria-hidden="true"></i>
        <span>Temas</span>
        <i class="bi bi-chevron-down theme-toggle-chevron" aria-hidden="true"></i>
    </button>
    <div class="theme-menu" role="menu" hidden>
        <p>Apariencia</p>
        <button type="button" role="menuitemradio" data-style="original" data-mode="light">
            <i class="bi bi-window" aria-hidden="true"></i>
            <span><strong>Original</strong><small>Diseno clasico claro</small></span>
            <i class="bi bi-check2 theme-check" aria-hidden="true"></i>
        </button>
        <button type="button" role="menuitemradio" data-style="original" data-mode="dark">
            <i class="bi bi-moon-stars" aria-hidden="true"></i>
            <span><strong>Original oscuro</strong><small>Diseno clasico en modo oscuro</small></span>
            <i class="bi bi-check2 theme-check" aria-hidden="true"></i>
        </button>
        <button type="button" role="menuitemradio" data-style="material" data-mode="light">
            <i class="bi bi-google" aria-hidden="true"></i>
            <span><strong>Material 3</strong><small>Estilo Google claro</small></span>
            <i class="bi bi-check2 theme-check" aria-hidden="true"></i>
        </button>
        <button type="button" role="menuitemradio" data-style="material" data-mode="dark">
            <i class="bi bi-circle-half" aria-hidden="true"></i>
            <span><strong>Material 3 oscuro</strong><small>Estilo Google en modo oscuro</small></span>
            <i class="bi bi-check2 theme-check" aria-hidden="true"></i>
        </button>
    </div>
</div>
<script>
(() => {
    const picker = document.currentScript.previousElementSibling;
    if (!picker?.classList.contains('theme-picker')) return;

    const storageKey = 'total-ground-inventory-appearance';
    const toggle = picker.querySelector('.theme-toggle');
    const menu = picker.querySelector('.theme-menu');
    const options = [...menu.querySelectorAll('[data-style][data-mode]')];

    const render = () => {
        const style = document.documentElement.dataset.uiTheme || 'original';
        const mode = document.documentElement.dataset.theme || 'light';
        options.forEach(option => {
            const selected = option.dataset.style === style && option.dataset.mode === mode;
            option.setAttribute('aria-checked', String(selected));
        });
    };
    const close = () => {
        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', event => {
        event.stopPropagation();
        const opening = menu.hidden;
        document.querySelectorAll('.theme-menu:not([hidden])').forEach(openMenu => {
            if (openMenu !== menu) openMenu.hidden = true;
        });
        menu.hidden = !opening;
        toggle.setAttribute('aria-expanded', String(opening));
        if (opening) menu.querySelector('[aria-checked="true"]')?.focus();
    });

    options.forEach(option => option.addEventListener('click', () => {
        const appearance = { style: option.dataset.style, mode: option.dataset.mode };
        document.documentElement.dataset.uiTheme = appearance.style;
        document.documentElement.dataset.theme = appearance.mode;
        document.documentElement.style.colorScheme = appearance.mode;
        localStorage.setItem(storageKey, JSON.stringify(appearance));
        render();
        close();
    }));

    document.addEventListener('click', event => {
        if (!picker.contains(event.target)) close();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !menu.hidden) {
            close();
            toggle.focus();
        }
    });
    render();
})();
</script>
