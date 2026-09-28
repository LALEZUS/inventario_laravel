<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Uso adecuado de la red - Total Ground</title>
    <style>
        @page { size: letter; margin: 1.35cm 1.7cm 1.35cm; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 2.05cm 0 1.55cm; color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.4; }
        .letterhead { position: fixed; top: -1.35cm; left: -1.7cm; z-index: -1; width: 21.59cm; height: 27.94cm; }
        .kicker { margin: 0 0 5px; color: #e30613; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; }
        h1 { margin: 0 0 5px; color: #111827; font-size: 25px; line-height: 1.15; }
        .intro { margin: 5px 0 11px; color: #526074; font-size: 10.5px; }
        .notice { margin: 0 0 12px; padding: 9px 11px; border-left: 4px solid #e30613; background: #fff1f2; color: #4b1b21; }
        .section { margin: 10px 0 0; page-break-inside: avoid; }
        h2 { margin: 0 0 5px; padding-bottom: 3px; border-bottom: 1px solid #d9dee7; color: #e30613; font-size: 12px; }
        p { margin: 4px 0 7px; }
        ul { margin: 6px 0 0 18px; padding: 0; }
        li { margin: 3px 0; }
        .columns { width: 100%; border-collapse: separate; border-spacing: 0 5px; page-break-inside: avoid; }
        .columns td { width: 50%; vertical-align: top; }
        .columns td:first-child { padding-right: 10px; }
        .columns td:last-child { padding-left: 10px; }
        .card { min-height: 78px; padding: 8px 10px; border: 1px solid #d9dee7; border-top: 3px solid #e30613; background: #fbfcfe; }
        .card h3 { margin: 0 0 3px; color: #202b3d; font-size: 10.5px; }
        .card p { margin: 0; color: #526074; }
    </style>
</head>
<body>
    @if($letterheadData)<img class="letterhead" src="{{ $letterheadData }}" alt="">@endif
    <h1>Uso adecuado de la red para externos</h1>
    <div class="notice"><strong>VPN OBLIGATORIO:</strong> el VPN debe permanecer conectado durante toda actividad relacionada con Total Ground. Solo debes desconectarlo temporalmente para ingresar al servidor empresarial; al terminar, vuelve a conectarlo inmediatamente.</div>

    <table class="columns">
        <tr>
            <td><div class="card"><h3>1. VPN obligatorio</h3><p>El VPN instalado por Sistemas debe estar conectado durante todo tu trabajo con recursos de Total Ground. No lo cierres ni lo desactives.</p></div></td>
            <td><div class="card"><h3>2. Acceso al servidor</h3><p>Desconecta el VPN solo para entrar al servidor empresarial y reconectalo al salir.</p></div></td>
        </tr>
        <tr>
            <td><div class="card"><h3>3. Navegacion y descargas</h3><p>Descarga programas solo de fuentes confiables y evita sitios, enlaces o archivos sospechosos. No instales software sin autorizacion.</p></div></td>
            <td><div class="card"><h3>4. Correo y mensajes</h3><p>Verifica remitentes y enlaces antes de abrirlos. No respondas solicitudes urgentes de informacion sensible sin confirmar su origen.</p></div></td>
        </tr>
        <tr>
            <td><div class="card"><h3>5. Equipos y dispositivos</h3><p>Mantén actualizado el equipo, bloquea la pantalla al alejarte y no conectes dispositivos USB desconocidos.</p></div></td>
            <td><div class="card"><h3>6. Informacion confidencial</h3><p>No compartas informacion de clientes, empleados, credenciales o configuraciones de red en canales personales o publicos.</p></div></td>
        </tr>
    </table>

    <section class="section">
        <h2>Buenas practicas diarias</h2>
        <ul>
            <li><strong>Antes de comenzar, verifica que el VPN este conectado y dejalo activo durante tu jornada.</strong></li>
            <li>Para ingresar al servidor empresarial, desconecta temporalmente el VPN y reconectalo inmediatamente despues.</li>
            <li>Guarda los archivos de trabajo en las ubicaciones aprobadas y respeta los respaldos establecidos.</li>
            <li>No desactives el antivirus, firewall u otras medidas de seguridad.</li>
            <li>Solicita apoyo a Sistemas cuando tengas dudas; no intentes solucionar incidentes de riesgo por tu cuenta.</li>
        </ul>
    </section>

    <div style="height: 2.8cm; margin-top: 12px; padding: 1.9cm 12px 0; color: #526074; font-size: 9px;">
        <div style="display: table; width: 100%; table-layout: fixed; color: #526074;">
            <div style="display: table-cell; width: 38%; padding-right: 16px;">
                <div style="border-top: 1px solid #9aa4b2; padding-top: 5px;">Nombre</div>
            </div>
            <div style="display: table-cell; width: 42%; padding: 0 8px;">
                <div style="border-top: 1px solid #9aa4b2; padding-top: 5px;">Firma</div>
            </div>
            <div style="display: table-cell; width: 20%; padding-left: 16px;">
                <div style="border-top: 1px solid #9aa4b2; padding-top: 5px;">Fecha</div>
            </div>
        </div>
    </div>

</body>
</html>
