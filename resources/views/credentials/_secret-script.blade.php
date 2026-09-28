@push('scripts')
<script>
document.addEventListener('click', async (event) => {
    const button = event.target.closest('.reveal-secret');
    const value = button?.parentElement?.querySelector('.secret-value');
    if (!button || !value || !value.dataset.secretUrl) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    if (value.dataset.visible === 'true') {
        value.textContent = '********';
        value.dataset.visible = 'false';
        button.textContent = 'Mostrar';
        return;
    }
    button.disabled = true;
    try {
        const response = await fetch(value.dataset.secretUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('No autorizado');
        const secrets = await response.json();
        value.textContent = secrets[value.dataset.secretField] || '-';
        value.dataset.visible = 'true';
        button.textContent = 'Ocultar';
    } catch (error) {
        value.textContent = 'No disponible';
    } finally {
        button.disabled = false;
    }
}, true);
</script>
@endpush
