<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sesi&oacute;n vencida | Inventario</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        :root { color-scheme: dark; font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; color: #f8fafc; background: radial-gradient(circle at 75% 20%, rgba(217, 0, 13, .28), transparent 35%), #0b1017; }
        main { width: min(100%, 560px); padding: 42px; border: 1px solid #263141; border-radius: 18px; background: rgba(21, 27, 37, .94); box-shadow: 0 24px 70px rgba(0, 0, 0, .35); text-align: center; }
        .brand { width: 220px; max-width: 80%; margin: 0 auto 34px; display: block; }
        .icon { width: 72px; height: 72px; margin: 0 auto 22px; display: grid; place-items: center; border-radius: 50%; background: #3a171c; color: #ff8991; font-size: 34px; font-weight: 900; }
        .code { margin: 0 0 9px; color: #ff8991; font-size: 12px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 0 0 12px; font-size: clamp(25px, 5vw, 34px); }
        p { margin: 0 auto 28px; max-width: 420px; color: #aeb8c7; line-height: 1.6; }
        a { min-height: 46px; padding: 0 22px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; background: #d9000d; color: #fff; text-decoration: none; font-weight: 800; }
        a:hover { background: #b9000b; }
        small { display: block; margin-top: 18px; color: #778397; }
        @media (max-width: 520px) { main { padding: 32px 22px; } }
    </style>
</head>
<body>
    <main>
        <img class="brand" src="{{ asset('images/logo-white.png') }}" alt="Total Ground">
        <div class="icon" aria-hidden="true">&#8635;</div>
        <p class="code">Sesi&oacute;n vencida &middot; Error 419</p>
        <h1>Tu sesi&oacute;n termin&oacute;</h1>
        <p>Por seguridad, la sesi&oacute;n del inventario expir&oacute; despu&eacute;s de un periodo de inactividad. Inicia sesi&oacute;n nuevamente para continuar.</p>
        <a href="{{ route('login') }}">Iniciar sesi&oacute;n nuevamente</a>
        <small>Los datos enviados despu&eacute;s de que venci&oacute; la sesi&oacute;n deben capturarse otra vez.</small>
    </main>
</body>
</html>
