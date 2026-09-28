# Inventario Total Ground - Migracion Laravel

Migracion progresiva del inventario existente a Laravel 12.

## Seguridad de la migracion

- El inventario actual permanece en `C:\xampp\htdocs\inventario`.
- Laravel usa la base aislada `inventario_laravel`.
- El respaldo inicial esta en `storage/app/migration-backups/inventario_baseline_20260814.sql`.
- Altas, edicion y eliminacion operan solamente sobre la copia `inventario_laravel`.

## Requisitos locales

- Apache y MySQL de XAMPP activos.
- PHP 8.2 o superior.
- Composer 2.

## Iniciar el proyecto

```powershell
cd C:\xampp\htdocs\inventario-laravel
C:\xampp\php\php.exe artisan serve --host=0.0.0.0 --port=8001
```

Abre `http://localhost:8001` e inicia sesion con un usuario existente del inventario.

## Pruebas

```powershell
cd C:\xampp\htdocs\inventario-laravel
C:\xampp\php\php.exe artisan test
```

## Funciones migradas

- Autenticacion con la tabla `users` existente.
- Sesiones protegidas y limite de intentos de acceso.
- Dashboard con indicadores reales de la copia.
- Listado paginado y busqueda de computadoras.
- Perfil de computadora con especificaciones y perifericos vinculados.
- Alta y edicion completa de computadoras.
- Carga privada, descarga y lectura automatica de archivos NFO.
- Autollenado de modelo, marca y especificaciones comunes desde NFO.
- Permisos por rol: administrador, soporte y consulta.
- Bitacora de creacion, actualizacion, eliminacion, login y logout.
- Eliminacion de computadoras limitada a administradores.
- Listado, busqueda, alta, edicion y detalle de celulares.
- Relacion de celulares y perifericos con empleados registrados.
- Listado, busqueda, alta, edicion y detalle de perifericos.
- Relacion navegable entre computadoras y perifericos.
- Adjuntos privados para computadoras, celulares y perifericos, incluyendo archivos historicos.
- Compartir celulares por WhatsApp con correo, contrasenas y patron para perfiles autorizados.
- Credenciales de celulares ocultas para perfiles de consulta y protegidas en la bitacora.
- Directorio de empleados con alta, edicion, detalle, permisos y auditoria.
- Perfil de empleado con computadoras, celulares y perifericos relacionados.
- Sincronizacion de nombres heredados cuando cambia el nombre de un empleado.
- Buscador global para computadoras, celulares, perifericos y empleados.
- Sugerencias en vivo sin cache y enlaces directos a cada ficha.
- Codigo QR individual para cada computadora con acceso protegido a su perfil.
- Registro auditado de escaneos QR con control para evitar eventos duplicados.
- Generacion de responsivas PDF con el membrete oficial de Total Ground.
- Responsivas con campos dinamicos: solo muestran datos disponibles del equipo y su NFO.
- Copia privada de cada responsiva guardada como archivo adjunto del activo.

## Siguiente etapa

Migrar los modulos restantes y agregar actualizaciones en tiempo real entre equipos conectados.
# Mejoras de plataforma

La aplicacion incluye estas integraciones opcionales, sin depender de Vite:

- **Alpine.js** se sirve localmente desde `public/js/alpine.min.js` para interacciones pequenas y progresivas.
- **PWA**: `public/manifest.webmanifest` y `public/sw.js` permiten instalar el inventario en celulares. Para que Chrome muestre la instalacion en la LAN se recomienda HTTPS; en HTTP solo funciona plenamente en `localhost`.
- **Imagenes WebP/AVIF**: ejecuta `php artisan inventory:optimize-images`. El comando conserva originales y genera derivados cuando PHP tiene GD/AVIF habilitado.
- **Telescope**: disponible en `/telescope` para desarrollo. Usa `TELESCOPE_ENABLED=true` solo en desarrollo y mantenlo apagado en produccion.
- **Sentry**: configurado mediante `SENTRY_LARAVEL_DSN`; deja la variable vacia si no se usara. En produccion solo enviara eventos cuando exista un DSN.
- **Redis**: Predis ya esta instalado. Cuando Redis este encendido, cambia en `.env` `CACHE_STORE=redis`, `SESSION_DRIVER=redis` y `SESSION_STORE=redis`, y limpia la configuracion con `php artisan optimize:clear`.

No se modifico el flujo de Vite ni se requiere compilar frontend para estas funciones.
