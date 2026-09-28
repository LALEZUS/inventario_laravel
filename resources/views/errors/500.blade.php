<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error 500 &middot; Error interno del servidor | Total Ground</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @include('shared.theme-init')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --tg-red: #d9000d;
            --tg-red-dark: #b3000a;
            --tg-red-glow: rgba(217, 0, 13, 0.28);
            --tg-red-subtle: rgba(217, 0, 13, 0.12);
            --bg-canvas: #090d14;
            --bg-card: rgba(16, 22, 33, 0.88);
            --bg-card-border: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --btn-sec-bg: rgba(255, 255, 255, 0.06);
            --btn-sec-border: rgba(255, 255, 255, 0.12);
            --btn-sec-hover: rgba(255, 255, 255, 0.12);
            --code-bg: rgba(7, 10, 16, 0.92);
            --font-main: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        html[data-theme="light"] {
            --bg-canvas: #f1f4f8;
            --bg-card: rgba(255, 255, 255, 0.95);
            --bg-card-border: rgba(203, 213, 225, 0.7);
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --btn-sec-bg: #f8fafc;
            --btn-sec-border: #cbd5e1;
            --btn-sec-hover: #e2e8f0;
            --code-bg: #f8fafc;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font-main);
            background-color: var(--bg-canvas);
            color: var(--text-primary);
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px 16px;
            position: relative;
            overflow-x: hidden;
            background-image:
                radial-gradient(ellipse 70% 50% at 50% -10%, var(--tg-red-glow), transparent 70%),
                radial-gradient(circle at 85% 90%, rgba(217, 0, 13, 0.08), transparent 45%),
                radial-gradient(circle at 15% 85%, rgba(30, 41, 59, 0.4), transparent 45%);
            background-attachment: fixed;
        }

        /* Ambient grid pattern */
        .ambient-grid {
            position: absolute;
            inset: 0;
            background-size: 40px 40px;
            background-image:
                linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            pointer-events: none;
            z-index: 0;
        }

        html[data-theme="light"] .ambient-grid {
            background-image:
                linear-gradient(to right, rgba(15, 23, 42, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(15, 23, 42, 0.03) 1px, transparent 1px);
        }

        .error-container {
            width: 100%;
            max-width: 680px;
            position: relative;
            z-index: 1;
            animation: fadeInScale 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: translateY(18px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Top Brand bar */
        .brand-header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 24px;
        }

        .brand-header img {
            height: 38px;
            width: auto;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.35));
        }

        html[data-theme="light"] .brand-header img {
            filter: invert(1) brightness(0.2) drop-shadow(0 2px 6px rgba(0,0,0,0.1));
        }

        /* Main Glass Card */
        .error-card {
            background: var(--bg-card);
            border: 1px solid var(--bg-card-border);
            border-radius: 24px;
            padding: clamp(32px, 6vw, 48px);
            box-shadow:
                0 25px 65px -12px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.04),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Top highlight strip */
        .error-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 15%;
            right: 15%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--tg-red), transparent);
            border-radius: 2px;
        }

        /* Server Illustration / Icon Area */
        .illustration-wrap {
            position: relative;
            width: 104px;
            height: 104px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pulse-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: radial-gradient(circle, var(--tg-red-glow) 0%, transparent 70%);
            animation: pulseAura 3s ease-in-out infinite;
        }

        @keyframes pulseAura {
            0%, 100% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.25); opacity: 0.9; }
        }

        .icon-circle {
            position: relative;
            width: 84px;
            height: 84px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(217, 0, 13, 0.18), rgba(217, 0, 13, 0.05));
            border: 1px solid rgba(217, 0, 13, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 28px -6px rgba(217, 0, 13, 0.35);
        }

        .icon-circle svg {
            width: 42px;
            height: 42px;
            color: #ff3342;
        }

        /* Status Badge */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: var(--tg-red-subtle);
            border: 1px solid rgba(217, 0, 13, 0.3);
            color: #ff525f;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .badge-status .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--tg-red);
            box-shadow: 0 0 10px var(--tg-red);
            animation: blinkDot 1.6s ease-in-out infinite;
        }

        @keyframes blinkDot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.35; }
        }

        /* Typography */
        h1 {
            font-size: clamp(26px, 5vw, 36px);
            font-weight: 800;
            letter-spacing: -0.025em;
            color: var(--text-primary);
            margin-bottom: 12px;
            line-height: 1.15;
        }

        .error-lead {
            font-size: clamp(15px, 2.8vw, 16px);
            line-height: 1.65;
            color: var(--text-secondary);
            max-width: 520px;
            margin: 0 auto 32px;
        }

        /* Action Buttons */
        .actions-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            align-items: center;
            margin-bottom: 32px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            height: 48px;
            padding: 0 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.2, 0.8, 0.2, 1);
            text-decoration: none;
            border: none;
        }

        .btn svg {
            width: 17px;
            height: 17px;
            flex-shrink: 0;
            transition: transform 0.25s ease;
        }

        .btn-primary {
            background: var(--tg-red);
            color: #ffffff;
            box-shadow: 0 8px 24px -4px rgba(217, 0, 13, 0.5);
        }

        .btn-primary:hover {
            background: var(--tg-red-dark);
            transform: translateY(-2px);
            box-shadow: 0 12px 28px -4px rgba(217, 0, 13, 0.65);
        }

        .btn-primary:hover svg {
            transform: rotate(180deg);
        }

        .btn-secondary {
            background: var(--btn-sec-bg);
            border: 1px solid var(--btn-sec-border);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: var(--btn-sec-hover);
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:active, .btn-primary:active {
            transform: translateY(0);
        }

        /* Details / Diagnostics Accordion */
        .diagnostics-box {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--bg-card-border);
            text-align: left;
        }

        details {
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid var(--bg-card-border);
            overflow: hidden;
            transition: all 0.2s ease;
        }

        html[data-theme="light"] details {
            background: #f8fafc;
        }

        details[open] {
            border-color: rgba(217, 0, 13, 0.3);
        }

        summary {
            list-style: none;
            cursor: pointer;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            user-select: none;
        }

        summary::-webkit-details-marker {
            display: none;
        }

        summary:hover {
            color: var(--text-primary);
        }

        .summary-arrow {
            transition: transform 0.2s ease;
        }

        details[open] .summary-arrow {
            transform: rotate(180deg);
        }

        .diag-body {
            padding: 16px 18px;
            border-top: 1px solid var(--bg-card-border);
            background: var(--code-bg);
            font-family: var(--font-mono);
            font-size: 12px;
            line-height: 1.6;
            color: #cbd5e1;
            overflow-x: auto;
            word-break: break-word;
        }

        html[data-theme="light"] .diag-body {
            color: #334155;
        }

        .diag-row {
            display: flex;
            gap: 12px;
            margin-bottom: 6px;
        }

        .diag-label {
            color: var(--text-muted);
            min-width: 90px;
            font-weight: 600;
        }

        .diag-value {
            color: #fca5a5;
        }

        /* Footer info */
        .card-footer {
            margin-top: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .support-link {
            color: var(--tg-red);
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .support-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 540px) {
            .actions-group {
                flex-direction: column;
                width: 100%;
            }
            .btn {
                width: 100%;
            }
            .card-footer {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="ambient-grid"></div>

    <div class="error-container">
        <header class="brand-header">
            <img src="{{ asset('images/logo-white.png') }}" alt="Total Ground">
        </header>

        <main class="error-card">
            <div class="illustration-wrap">
                <div class="pulse-ring"></div>
                <div class="icon-circle">
                    <!-- Modern Server Alert SVG -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                        <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                        <line x1="6" y1="6" x2="6.01" y2="6"></line>
                        <line x1="6" y1="18" x2="6.01" y2="18"></line>
                        <path d="M16 6h2"></path>
                        <path d="M12 18h6"></path>
                        <circle cx="17" cy="6" r="3" fill="#ff3342" stroke="none"></circle>
                        <path d="M17 5v2" stroke="#fff" stroke-width="1.5"></path>
                    </svg>
                </div>
            </div>

            <div class="badge-status">
                <span class="dot"></span>
                <span>Error 500 &middot; Servidor</span>
            </div>

            <h1>Algo no salió como esperábamos</h1>

            <p class="error-lead">
                El servidor encontró una dificultad técnica inesperada al procesar esta solicitud. El incidente ha sido notificado al registro del sistema para su atención.
            </p>

            <div class="actions-group">
                <button type="button" class="btn btn-primary" onclick="window.location.reload();">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.19"/>
                    </svg>
                    <span>Reintentar página</span>
                </button>

                <a href="{{ url('/') }}" class="btn btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Ir al inicio</span>
                </a>

                <button type="button" class="btn btn-secondary" onclick="window.history.length > 1 ? window.history.back() : (window.location.href = '{{ url('/') }}');">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <span>Regresar</span>
                </button>
            </div>

            <div class="diagnostics-box">
                <details>
                    <summary>
                        <span>Información técnica de diagnóstico</span>
                        <svg class="summary-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </summary>
                    <div class="diag-body">
                        <div class="diag-row">
                            <span class="diag-label">Ruta:</span>
                            <span>{{ request()->path() }}</span>
                        </div>
                        <div class="diag-row">
                            <span class="diag-label">Método:</span>
                            <span>{{ request()->method() }}</span>
                        </div>
                        <div class="diag-row">
                            <span class="diag-label">Hora:</span>
                            <span>{{ now()->format('d/m/Y H:i:s') }}</span>
                        </div>
                        <div class="diag-row">
                            <span class="diag-label">Referencia:</span>
                            <span>TG-{{ strtoupper(substr(sha1(request()->fullUrl() . now()->format('YmdHi')), 0, 8)) }}</span>
                        </div>
                        @if (!empty($exception) && ($exception->getMessage() || config('app.debug')))
                            <div class="diag-row" style="margin-top: 8px; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 8px;">
                                <span class="diag-label">Detalle:</span>
                                <span class="diag-value">{{ $exception->getMessage() ?: 'No message provided' }}</span>
                            </div>
                            @if (config('app.debug') && method_exists($exception, 'getFile'))
                                <div class="diag-row">
                                    <span class="diag-label">Archivo:</span>
                                    <span>{{ basename($exception->getFile()) }}:{{ $exception->getLine() }}</span>
                                </div>
                            @endif
                        @endif
                    </div>
                </details>
            </div>

            <footer class="card-footer">
                <span>Inventario IT &middot; Total Ground</span>
                <a href="{{ url('/tutoriales') }}" class="support-link">
                    <span>Centro de ayuda y soporte</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </footer>
        </main>
    </div>
</body>
</html>
