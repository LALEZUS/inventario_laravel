@if ($auditLogs->isNotEmpty())
    @php
        $fieldLabels = [
            'name' => 'Nombre',
            'type' => 'Tipo',
            'vendor' => 'Proveedor',
            'status' => 'Estado',
            'expiration_date' => 'Vencimiento',
            'link' => 'Enlace',
            'comments' => 'Comentarios',
            'key_value' => 'Usuario / Llave',
            'password' => 'Contraseña',
            'contraseña' => 'Contraseña',
            'email' => 'Correo',
            'account_type' => 'Tipo de cuenta',
            'assigned_to' => 'Asignado a',
            'employee_id' => 'Empleado ID',
            'admin_password' => 'Contraseña Admin',
            'serial' => 'Número de serie',
            'brand' => 'Marca',
            'model' => 'Modelo',
            'category' => 'Categoría',
        ];
    @endphp
    <section class="panel">
        <div class="panel-header">
            <div>
                <p class="eyebrow">Trazabilidad</p>
                <h2>Bitacora del registro</h2>
            </div>
            <strong>{{ $auditLogs->count() }}</strong>
        </div>
        <div class="audit-timeline">
            @foreach ($auditLogs as $log)
                @php
                    $diffs = [];
                    if (is_array($log->before_data) && is_array($log->after_data)) {
                        $ignored = ['id', 'created_at', 'updated_at'];
                        $keys = array_unique(array_merge(array_keys($log->before_data), array_keys($log->after_data)));
                        foreach ($keys as $k) {
                            if (in_array($k, $ignored, true)) continue;
                            $oldVal = $log->before_data[$k] ?? null;
                            $newVal = $log->after_data[$k] ?? null;
                            if (blank($oldVal) && blank($newVal)) continue;
                            if ($oldVal !== $newVal) {
                                $diffs[$k] = ['old' => $oldVal, 'new' => $newVal];
                            }
                        }
                    }
                @endphp
                <article>
                    <span class="audit-dot"></span>
                    <div>
                        <strong>{{ ucfirst($log->action) }} por {{ $log->user_name ?: 'Sistema' }}</strong>
                        <small>{{ $log->created_at?->format('d/m/Y H:i') }} · {{ $log->role ?: 'sin rol' }} · {{ $log->ip_address ?: 'sin IP' }}</small>

                        @if (!empty($diffs))
                            <div class="audit-changes">
                                @foreach ($diffs as $field => $diff)
                                    @php
                                        $isSensitive = in_array($field, ['password', 'contraseña', 'key_value', 'admin_password'], true);
                                        $fieldLabel = $fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field));
                                        $oldDisplay = blank($diff['old']) ? '(vacío)' : $diff['old'];
                                        $newDisplay = blank($diff['new']) ? '(vacío)' : $diff['new'];
                                    @endphp
                                    <div class="audit-change-item @if($isSensitive) is-sensitive @endif">
                                        <span class="audit-change-label">{{ $fieldLabel }}</span>
                                        <div class="audit-diff-body">
                                            <span class="diff-tag diff-tag-old @if($isSensitive) diff-tag-sensitive @endif">
                                                <span class="diff-tag-title">Anterior:</span>
                                                @if ($isSensitive && filled($diff['old']) && $diff['old'] !== '[PROTECTED]')
                                                    <code class="diff-val secret-maskable" data-secret-raw="{{ $diff['old'] }}" data-revealed="false">••••••••</code>
                                                    <button type="button" class="audit-eye-btn" aria-label="Mostrar valor anterior" title="Mostrar">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                                    </button>
                                                @else
                                                    <code class="diff-val diff-old-val">{{ $oldDisplay }}</code>
                                                @endif
                                            </span>
                                            <span class="diff-arrow" aria-hidden="true">&rarr;</span>
                                            <span class="diff-tag diff-tag-new @if($isSensitive) diff-tag-sensitive @endif">
                                                <span class="diff-tag-title">Nueva:</span>
                                                @if ($isSensitive && filled($diff['new']) && $diff['new'] !== '[PROTECTED]')
                                                    <code class="diff-val secret-maskable" data-secret-raw="{{ $diff['new'] }}" data-revealed="false">••••••••</code>
                                                    <button type="button" class="audit-eye-btn" aria-label="Mostrar valor nuevo" title="Mostrar">
                                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                                    </button>
                                                @else
                                                    <code class="diff-val diff-new-val">{{ $newDisplay }}</code>
                                                @endif
                                            </span>
                                            @if ($isSensitive && filled($diff['old']) && $diff['old'] !== '[PROTECTED]')
                                                <button type="button" class="audit-copy-btn" data-copy-text="{{ $diff['old'] }}" title="Copiar contraseña anterior">
                                                    <i class="bi bi-clipboard" aria-hidden="true"></i> Copiar anterior
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <script>
    if (!window._auditListenersAttached) {
        window._auditListenersAttached = true;

        // Toggle ojo para descubrir / ocultar secretos
        document.addEventListener('click', (e) => {
            const eyeBtn = e.target.closest('.audit-eye-btn');
            if (!eyeBtn) return;
            e.preventDefault();
            e.stopPropagation();

            const container = eyeBtn.closest('.diff-tag');
            const codeEl = container?.querySelector('.secret-maskable');
            if (!codeEl) return;

            const isRevealed = codeEl.dataset.revealed === 'true';
            if (isRevealed) {
                codeEl.textContent = '••••••••';
                codeEl.dataset.revealed = 'false';
                eyeBtn.innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i>';
                eyeBtn.title = 'Mostrar';
                eyeBtn.setAttribute('aria-label', 'Mostrar');
            } else {
                codeEl.textContent = codeEl.dataset.secretRaw;
                codeEl.dataset.revealed = 'true';
                eyeBtn.innerHTML = '<i class="bi bi-eye-slash" aria-hidden="true"></i>';
                eyeBtn.title = 'Ocultar';
                eyeBtn.setAttribute('aria-label', 'Ocultar');
            }
        });

        // Botón copiar anterior
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.audit-copy-btn');
            if (!btn) return;
            e.preventDefault();
            const text = btn.dataset.copyText;
            if (!text) return;
            navigator.clipboard.writeText(text).then(() => {
                const originalHtml = btn.innerHTML;
                btn.classList.add('copied');
                btn.innerHTML = '<i class="bi bi-check2"></i> ¡Copiada!';
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = originalHtml;
                }, 1800);
            }).catch(() => {
                const input = document.createElement('input');
                input.value = text;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                btn.classList.add('copied');
                btn.innerHTML = '<i class="bi bi-check2"></i> ¡Copiada!';
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = '<i class="bi bi-clipboard"></i> Copiar anterior';
                }, 1800);
            });
        });
    }
    </script>
@endif
