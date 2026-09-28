<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesion | Inventario Total Ground</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">
    @include('shared.theme-init')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-panel">
            <div class="login-theme-control"><x-theme-toggle /></div>
            <form method="POST" action="{{ route('login.store') }}" class="login-card">
                @csrf
                <div class="login-brand">
                    <picture>
                        <source srcset="{{ asset('images/logo-white.avif') }}" type="image/avif">
                        <source srcset="{{ asset('images/logo-white.webp') }}" type="image/webp">
                        <img src="{{ asset('images/logo-white.png') }}" alt="Total Ground">
                    </picture>
                </div>
                <div class="login-mark">IT</div>
                <h1 class="login-product">Inventario TI</h1>
                <p class="login-kicker">Inventario empresarial</p>
                <h2>Iniciar sesion</h2>
                <p class="login-subtitle">Bienvenido de nuevo. Administra tus activos con informacion segura y centralizada.</p>

                <label for="username">Usuario</label>
                <div class="login-input-wrap">
                    <i class="bi bi-person" aria-hidden="true"></i>
                    <input id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="Escribe tu usuario">
                </div>

                <label for="password">Contrasena</label>
                <div class="login-input-wrap">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Escribe tu contrasena">
                    <button class="login-password-toggle" type="button" aria-label="Mostrar contrasena" aria-controls="password">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>

                <label class="remember-option"><input type="checkbox" name="remember" value="1"> Mantener sesion</label>

                @if ($errors->any())
                    <div class="form-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <button class="button button-primary button-block login-submit" type="submit">
                    <span>Iniciar sesion</span>
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
                <small class="login-help"><i class="bi bi-shield-check" aria-hidden="true"></i> Acceso protegido para personal autorizado</small>
            </form>
        </section>
    </main>
    <script>
        document.querySelector('.login-password-toggle')?.addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = this.querySelector('i');
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            icon.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
            this.setAttribute('aria-label', visible ? 'Mostrar contrasena' : 'Ocultar contrasena');
        });
    </script>
</body>
</html>
