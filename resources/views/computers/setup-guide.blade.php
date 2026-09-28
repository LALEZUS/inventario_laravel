@extends('layouts.app')

@section('title', 'Guia de inicio de computadora')
@section('page-title', 'Guia de inicio de computadora')

@section('content')
    <div class="setup-guide-shell" x-data="computerSetupGuide()" x-cloak>
        <section class="page-intro setup-guide-intro">
            <div>
                <p class="eyebrow">Preparacion de equipos</p>
                <h2>Guia de inicio de una nueva PC</h2>
                <p>Configura el equipo de la empresa paso a paso y deja evidencia de cada verificacion.</p>
            </div>
            <div class="page-actions setup-guide-actions">
                <a class="button button-secondary" href="{{ route('computers.index') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Computadoras</a>
                <button class="button button-secondary" type="button" @click="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
                <button class="button button-danger" type="button" @click="reset()"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reiniciar</button>
            </div>
        </section>

        <section class="setup-progress panel" aria-label="Progreso de la guia">
            <div>
                <p class="eyebrow">Progreso de configuracion</p>
                <strong><span x-text="completed"></span> de <span x-text="total"></span> pasos completados</strong>
            </div>
            <div class="setup-progress-track" aria-hidden="true"><span :style="`width: ${percent}%`"></span></div>
            <b x-text="`${percent}%`"></b>
        </section>

        <div class="setup-guide-layout">
            <main class="setup-guide-main">
                <section class="setup-phase panel">
                    <div class="setup-phase-heading"><span>01</span><div><p class="eyebrow">Primer inicio</p><h3>Configura Windows y la red</h3><p>Completa el asistente inicial conectado a la red de la empresa.</p></div></div>
                    <div class="setup-step-list">
                        <label class="setup-step"><input type="checkbox" @change="toggle('network')" :checked="isChecked('network')"><span><b>Conecta el equipo a la red empresarial</b><small>Usa la red de Sistemas y valida que tenga Internet. <a href="{{ url('/red/empresariales/3') }}" @click.stop>Ver red en inventario</a>.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('name')" :checked="isChecked('name')"><span><b>Define el nombre del equipo</b><small>Usa el formato indicado por TI; normalmente comienza con <code>TI</code>.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('updates')" :checked="isChecked('updates')"><span><b>Instala todas las actualizaciones de Windows</b><small>Reinicia las veces necesarias y espera aproximadamente una hora si hay muchas pendientes.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('microsoft')" :checked="isChecked('microsoft')"><span><b>Inicia sesion con la cuenta de Microsoft</b><small>Consulta la cuenta autorizada en <a href="{{ url('/credenciales') }}" @click.stop>Credenciales</a>. Referencia habitual: <code>offiicetg210@hotmail.com</code>.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('pin')" :checked="isChecked('pin')"><span><b>Configura el PIN de administrador</b><small>Usa el formato establecido por TI: <code>Ground[AÑO].h</code>. Para 2026: <code>Ground2026.h</code>.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('privacy')" :checked="isChecked('privacy')"><span><b>Revisa privacidad y permisos</b><small>Desactiva las dos ultimas opciones del apartado de privacidad.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('new-device')" :checked="isChecked('new-device')"><span><b>Elige configurar como dispositivo nuevo</b><small>No restaures una copia personal.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('protection')" :checked="isChecked('protection')"><span><b>Acepta el registro y la proteccion del dispositivo</b><small>Continua con las opciones recomendadas por la empresa.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('optional')" :checked="isChecked('optional')"><span><b>Omite personalizacion, telefono y respaldo de fotos</b><small>Selecciona omitir o configurar mas tarde en esos tres pasos.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('browser')" :checked="isChecked('browser')"><span><b>Omite la importacion de navegacion</b><small>Cuando pregunte por datos recientes de navegacion, elige <strong>Ahora no</strong>.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('m365')" :checked="isChecked('m365')"><span><b>Rechaza la prueba de Microsoft 365</b><small>Selecciona rechazar o continuar sin prueba cuando aparezca, incluso si se muestra una segunda confirmacion.</small></span></label>
                    </div>
                </section>

                <section class="setup-phase panel">
                    <div class="setup-phase-heading"><span>02</span><div><p class="eyebrow">Seguridad y almacenamiento</p><h3>Prepara el equipo para trabajo</h3><p>Deja el almacenamiento y las protecciones en el estado definido por TI.</p></div></div>
                    <div class="setup-step-list">
                        <label class="setup-step"><input type="checkbox" @change="toggle('onedrive')" :checked="isChecked('onedrive')"><span><b>Desinstala OneDrive</b><small>Retira la sincronizacion personal para evitar mezclar documentos.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('bitlocker')" :checked="isChecked('bitlocker')"><span><b>Desactiva BitLocker</b><small>Confirma el estado solicitado por TI antes de continuar.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('partition')" :checked="isChecked('partition')"><span><b>Divide el almacenamiento</b><small>Reserva aproximadamente 60% para <code>C:</code> y 40% para <code>Z:</code>. Nombra la unidad <code>ARCHIVOS</code>.</small></span></label>
                    </div>
                </section>

                <section class="setup-phase panel">
                    <div class="setup-phase-heading"><span>03</span><div><p class="eyebrow">Usuario de trabajo</p><h3>Crea el perfil local</h3><p>El perfil local sera el entorno diario del usuario.</p></div></div>
                    <div class="setup-step-list">
                        <label class="setup-step"><input type="checkbox" @change="toggle('local-user')" :checked="isChecked('local-user')"><span><b>Crea el usuario local</b><small>Usa el nombre indicado por TI. La contrasena inicial es <code>tierra</code>; respuestas: <code>total</code>, <code>ground</code> y <code>total ground</code>.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('local-privacy')" :checked="isChecked('local-privacy')"><span><b>Inicia el nuevo perfil y revisa privacidad</b><small>Entra al perfil local y aplica nuevamente las preferencias de privacidad.</small></span></label>
                    </div>
                </section>

                <section class="setup-phase panel">
                    <div class="setup-phase-heading"><span>04</span><div><p class="eyebrow">Inventario y software</p><h3>Instala las herramientas autorizadas</h3><p>Registra el equipo antes de instalar y conserva los instaladores aprobados.</p></div></div>
                    <div class="setup-step-list">
                        <label class="setup-step"><input type="checkbox" @change="toggle('inventory')" :checked="isChecked('inventory')"><span><b>Registra la computadora en el inventario</b><small>Crea el registro, carga el archivo <code>.nfo</code>, revisa las especificaciones y completa AnyDesk, RustDesk y valor si aplica.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('files')" :checked="isChecked('files')"><span><b>Abre Archivos generales</b><small>Consulta los instaladores desde <a href="{{ url('/archivos-generales') }}" @click.stop>Archivos generales</a>.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('software')" :checked="isChecked('software')"><span><b>Instala Zoom, Webex, RustDesk, Office, Chrome, AnyDesk y Adobe Reader</b><small>Instala solo versiones aprobadas y valida que cada programa abra correctamente.</small></span></label>
                    </div>
                </section>

                <section class="setup-phase panel">
                    <div class="setup-phase-heading"><span>05</span><div><p class="eyebrow">Microsoft 365</p><h3>Activa Office con la cuenta corporativa</h3><p>Usa la cuenta de Microsoft 365 asignada al usuario.</p></div></div>
                    <div class="setup-step-list">
                        <label class="setup-step"><input type="checkbox" @change="toggle('office-account')" :checked="isChecked('office-account')"><span><b>Consulta el correo de Microsoft 365</b><small>Busca la cuenta asignada en <a href="{{ url('/correos?type=microsoft') }}" @click.stop>Correos de Microsoft</a>.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('office-login')" :checked="isChecked('office-login')"><span><b>Inicia sesion en Office</b><small>Cuando pregunte si deseas usar la cuenta en Windows, elige <strong>No, solo en esta aplicacion</strong>.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('office-check')" :checked="isChecked('office-check')"><span><b>Valida la activacion</b><small>Abre Word, Excel y PowerPoint y confirma que aparecen activados.</small></span></label>
                    </div>
                </section>

                <section class="setup-phase panel">
                    <div class="setup-phase-heading"><span>06</span><div><p class="eyebrow">Documentos</p><h3>Configura la carpeta de trabajo</h3><p>Todos los documentos del usuario deben quedar en la unidad de archivos.</p></div></div>
                    <div class="setup-step-list">
                        <label class="setup-step"><input type="checkbox" @change="toggle('documents-folder')" :checked="isChecked('documents-folder')"><span><b>Crea <code>Z:\Documentos</code></b><small>Usa exactamente esa carpeta para centralizar los archivos de trabajo.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('word-path')" :checked="isChecked('word-path')"><span><b>Configura Word</b><small>Establece <code>Z:\Documentos</code> como ubicacion predeterminada para guardar.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('excel-path')" :checked="isChecked('excel-path')"><span><b>Configura Excel</b><small>Establece <code>Z:\Documentos</code> como ubicacion predeterminada para guardar.</small></span></label>
                        <label class="setup-step"><input type="checkbox" @change="toggle('powerpoint-path')" :checked="isChecked('powerpoint-path')"><span><b>Configura PowerPoint</b><small>Establece <code>Z:\Documentos</code> como ubicacion predeterminada para guardar.</small></span></label>
                    </div>
                </section>

                <section class="setup-phase panel setup-final-phase">
                    <div class="setup-phase-heading"><span>07</span><div><p class="eyebrow">Entrega</p><h3>Verificacion final</h3><p>No entregues el equipo hasta validar todos los puntos.</p></div></div>
                    <div class="setup-final-checks">
                        <label><input type="checkbox" @change="toggle('final-network')" :checked="isChecked('final-network')"> Red y nombre del equipo confirmados</label>
                        <label><input type="checkbox" @change="toggle('final-inventory')" :checked="isChecked('final-inventory')"> Registro, .nfo y accesos remotos guardados</label>
                        <label><input type="checkbox" @change="toggle('final-software')" :checked="isChecked('final-software')"> Programas instalados y probados</label>
                        <label><input type="checkbox" @change="toggle('final-office')" :checked="isChecked('final-office')"> Word, Excel y PowerPoint activados</label>
                        <label><input type="checkbox" @change="toggle('final-documents')" :checked="isChecked('final-documents')"> Rutas de guardado apuntan a <code>Z:\Documentos</code></label>
                    </div>
                </section>
            </main>

            <aside class="setup-guide-sidebar">
                <section class="panel setup-reference-card">
                    <p class="eyebrow">Datos de referencia</p>
                    <h3>Antes de comenzar</h3>
                    <dl>
                        <div><dt>Nombre usual</dt><dd><code>TI...</code></dd></div>
                        <div><dt>PIN admin</dt><dd><code>Ground[AÑO].h</code></dd></div>
                        <div><dt>Usuario local</dt><dd><code>tierra</code></dd></div>
                        <div><dt>Disco</dt><dd><code>C: 60% / Z: 40%</code></dd></div>
                        <div><dt>Documentos</dt><dd><code>Z:\Documentos</code></dd></div>
                    </dl>
                </section>
                <section class="panel setup-links-card">
                    <p class="eyebrow">Accesos rapidos</p>
                    <h3>Secciones relacionadas</h3>
                    <a href="{{ url('/red/empresariales/3') }}"><i class="bi bi-wifi" aria-hidden="true"></i> Red de Sistemas</a>
                    <a href="{{ url('/credenciales') }}"><i class="bi bi-key" aria-hidden="true"></i> Credenciales</a>
                    <a href="{{ url('/archivos-generales') }}"><i class="bi bi-folder2-open" aria-hidden="true"></i> Archivos generales</a>
                    <a href="{{ url('/correos?type=microsoft') }}"><i class="bi bi-envelope" aria-hidden="true"></i> Correos Microsoft</a>
                </section>
                <section class="setup-notice">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    <div><strong>Protege los accesos</strong><p>La guia guarda solo el avance de casillas en este navegador. Las contrasenas deben consultarse en los modulos protegidos del inventario.</p></div>
                </section>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.computerSetupGuide = function () {
            return {
                checked: JSON.parse(localStorage.getItem('computer-setup-guide-v1') || '{}'),
                total: 31,
                init() {
                    this.$watch('checked', value => localStorage.setItem('computer-setup-guide-v1', JSON.stringify(value)));
                },
                isChecked(id) { return Boolean(this.checked[id]); },
                toggle(id) { this.checked = { ...this.checked, [id]: !this.checked[id] }; },
                reset() { this.checked = {}; },
                get completed() { return Object.values(this.checked).filter(Boolean).length; },
                get percent() { return Math.min(100, Math.round((this.completed / this.total) * 100)); }
            };
        };
    </script>
@endpush
