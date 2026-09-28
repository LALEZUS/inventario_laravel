<script>
(() => {
    const storageKey = 'total-ground-inventory-appearance';
    let appearance = { style: 'original', mode: 'light' };

    try {
        const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
        if (['original', 'material'].includes(saved?.style) && ['light', 'dark'].includes(saved?.mode)) {
            appearance = saved;
        }
    } catch (_) {
        localStorage.removeItem(storageKey);
    }

    document.documentElement.dataset.uiTheme = appearance.style;
    document.documentElement.dataset.theme = appearance.mode;
    document.documentElement.style.colorScheme = appearance.mode;
})();
</script>
