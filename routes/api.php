<?php

use App\Http\Controllers\Api\V1\AccountCredentialApiController;
use App\Http\Controllers\Api\V1\OutlookAccountApiController;
use App\Http\Controllers\Api\V1\ApiAuthController;
use App\Http\Controllers\Api\V1\AssetFileApiController;
use App\Http\Controllers\Api\V1\CellphoneApiController;
use App\Http\Controllers\Api\V1\CalendarReminderApiController;
use App\Http\Controllers\Api\V1\ConsumableApiController;
use App\Http\Controllers\Api\V1\EmployeeApiController;
use App\Http\Controllers\Api\V1\EnterpriseNetworkApiController;
use App\Http\Controllers\Api\V1\FileCatalogApiController;
use App\Http\Controllers\Api\V1\GalleryApiController;
use App\Http\Controllers\Api\V1\TutorialApiController;
use App\Http\Controllers\Api\V1\NoteApiController;



use App\Http\Controllers\Api\V1\HardwareAssetApiController;
use App\Http\Controllers\Api\V1\HardwareAssetDetailApiController;
use App\Http\Controllers\Api\V1\NetworkDeviceApiController;
use App\Http\Controllers\Api\V1\PeripheralApiController;
use App\Http\Controllers\Api\V1\PrinterApiController;
use App\Http\Controllers\Api\V1\SearchApiController;
use App\Http\Controllers\Api\V1\WatchguardUserApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [ApiAuthController::class, 'login']);
Route::post('/auth/login', [ApiAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [ApiAuthController::class, 'logout']);
    Route::post('/auth/logout', [ApiAuthController::class, 'logout']);
    Route::get('/me', [ApiAuthController::class, 'me']);
    Route::get('/auth/me', [ApiAuthController::class, 'me']);
    Route::get('/buscar', [SearchApiController::class, 'search']);

    // Agenda global REST API
    Route::apiResource('agenda', CalendarReminderApiController::class)
        ->parameters(['agenda' => 'calendarReminder'])
        ->names([
            'index' => 'api.v1.agenda.index',
            'store' => 'api.v1.agenda.store',
            'show' => 'api.v1.agenda.show',
            'update' => 'api.v1.agenda.update',
            'destroy' => 'api.v1.agenda.destroy',
        ]);

    // Asset Files (Fotos y Archivos adjuntos a Equipos/Celulares/Impresoras)
    Route::get('/archivos/{file}/vista', [AssetFileApiController::class, 'preview'])->name('api.v1.files.preview');
    Route::get('/archivos/{file}/descargar', [AssetFileApiController::class, 'download'])->name('api.v1.files.download');

    // Empleados REST API
    Route::apiResource('empleados', EmployeeApiController::class)
        ->parameters(['empleados' => 'employee'])
        ->names([
            'index' => 'api.v1.empleados.index',
            'store' => 'api.v1.empleados.store',
            'show' => 'api.v1.empleados.show',
            'update' => 'api.v1.empleados.update',
            'destroy' => 'api.v1.empleados.destroy',
        ]);

    // Computadoras / Hardware REST API
    Route::get('/computadoras/stats', [HardwareAssetApiController::class, 'stats'])->name('api.v1.computadoras.stats');
    Route::get('/computadoras/opciones-busqueda', [HardwareAssetApiController::class, 'searchOptions'])->name('api.v1.computadoras.search_options');

    // Computadoras - Asignación y Devolución
    Route::post('/computadoras/{computer}/asignar', [HardwareAssetApiController::class, 'assign'])->name('api.v1.computadoras.assign');
    Route::post('/computadoras/{computer}/devolver/{assignment?}', [HardwareAssetApiController::class, 'returnAsset'])->name('api.v1.computadoras.return');

    // Computadoras - Submódulos Adicionales (HardwareAssetDetailApiController)
    Route::get('/computadoras/{computer}/credenciales', [HardwareAssetDetailApiController::class, 'credentials'])->name('api.v1.computadoras.credentials');
    Route::get('/computadoras/{computer}/fotos', [HardwareAssetDetailApiController::class, 'photos'])->name('api.v1.computadoras.photos');
    Route::post('/computadoras/{computer}/fotos', [HardwareAssetDetailApiController::class, 'uploadPhoto'])->name('api.v1.computadoras.upload_photo');
    Route::get('/computadoras/{computer}/archivos', [HardwareAssetDetailApiController::class, 'files'])->name('api.v1.computadoras.files');
    Route::post('/computadoras/{computer}/archivos', [HardwareAssetDetailApiController::class, 'uploadFile'])->name('api.v1.computadoras.upload_file');
    Route::get('/computadoras/{computer}/asignaciones', [HardwareAssetDetailApiController::class, 'assignments'])->name('api.v1.computadoras.assignments');
    Route::get('/computadoras/{computer}/mantenimientos', [HardwareAssetDetailApiController::class, 'maintenanceLogs'])->name('api.v1.computadoras.maintenances');
    Route::get('/computadoras/{computer}/bitacora', [HardwareAssetDetailApiController::class, 'auditLogs'])->name('api.v1.computadoras.audit');
    Route::post('/computadoras/{computer}/responsiva/vista-previa', [HardwareAssetDetailApiController::class, 'previewResponsiva'])->name('api.v1.computadoras.responsiva.preview');
    Route::post('/computadoras/{computer}/responsiva', [HardwareAssetDetailApiController::class, 'downloadResponsiva'])->name('api.v1.computadoras.responsiva');
    Route::get('/computadoras/{computer}/responsiva', [HardwareAssetDetailApiController::class, 'downloadResponsiva']);

    Route::apiResource('computadoras', HardwareAssetApiController::class)
        ->parameters(['computadoras' => 'computer'])
        ->names([
            'index' => 'api.v1.computadoras.index',
            'store' => 'api.v1.computadoras.store',
            'show' => 'api.v1.computadoras.show',
            'update' => 'api.v1.computadoras.update',
            'destroy' => 'api.v1.computadoras.destroy',
        ]);

    // Celulares REST API
    Route::get('/celulares/{cellphone}/credenciales', [CellphoneApiController::class, 'credentials'])->name('api.v1.celulares.credentials');
    Route::get('/celulares/{cellphone}/fotos', [CellphoneApiController::class, 'photos'])->name('api.v1.celulares.photos');
    Route::post('/celulares/{cellphone}/fotos', [CellphoneApiController::class, 'uploadPhoto'])->name('api.v1.celulares.upload_photo');
    Route::get('/celulares/{cellphone}/archivos', [CellphoneApiController::class, 'files'])->name('api.v1.celulares.files');
    Route::post('/celulares/{cellphone}/archivos', [CellphoneApiController::class, 'uploadFile'])->name('api.v1.celulares.upload_file');
    Route::get('/celulares/{cellphone}/bitacora', [CellphoneApiController::class, 'auditLogs'])->name('api.v1.celulares.audit');

    Route::apiResource('celulares', CellphoneApiController::class)
        ->parameters(['celulares' => 'cellphone'])
        ->names([
            'index' => 'api.v1.celulares.index',
            'store' => 'api.v1.celulares.store',
            'show' => 'api.v1.celulares.show',
            'update' => 'api.v1.celulares.update',
            'destroy' => 'api.v1.celulares.destroy',
        ]);

    // Periféricos REST API
    Route::apiResource('perifericos', PeripheralApiController::class)
        ->parameters(['perifericos' => 'peripheral'])
        ->names([
            'index' => 'api.v1.perifericos.index',
            'store' => 'api.v1.perifericos.store',
            'show' => 'api.v1.perifericos.show',
            'update' => 'api.v1.perifericos.update',
            'destroy' => 'api.v1.perifericos.destroy',
        ]);

    // Impresoras REST API
    Route::get('/impresoras/opciones', [PrinterApiController::class, 'options'])->name('api.v1.impresoras.options');
    Route::get('/impresoras/{printer}/consumibles', [PrinterApiController::class, 'consumables'])->name('api.v1.impresoras.consumables');
    Route::get('/impresoras/{printer}/archivos', [PrinterApiController::class, 'files'])->name('api.v1.impresoras.files');
    Route::get('/impresoras/{printer}/bitacora', [PrinterApiController::class, 'auditLogs'])->name('api.v1.impresoras.audit');

    Route::apiResource('impresoras', PrinterApiController::class)
        ->parameters(['impresoras' => 'printer'])
        ->names([
            'index' => 'api.v1.impresoras.index',
            'store' => 'api.v1.impresoras.store',
            'show' => 'api.v1.impresoras.show',
            'update' => 'api.v1.impresoras.update',
            'destroy' => 'api.v1.impresoras.destroy',
        ]);

    // Consumibles (Tintas y Tóner) REST API
    Route::get('/consumibles/stats', [ConsumableApiController::class, 'stats'])->name('api.v1.consumibles.stats');
    Route::get('/consumibles/tintas', [ConsumableApiController::class, 'inksIndex'])->name('api.v1.consumibles.inks.index');
    Route::post('/consumibles/tintas', [ConsumableApiController::class, 'inksStore'])->name('api.v1.consumibles.inks.store');
    Route::get('/consumibles/tintas/{ink}', [ConsumableApiController::class, 'inksShow'])->name('api.v1.consumibles.inks.show');
    Route::put('/consumibles/tintas/{ink}', [ConsumableApiController::class, 'inksUpdate'])->name('api.v1.consumibles.inks.update');
    Route::delete('/consumibles/tintas/{ink}', [ConsumableApiController::class, 'inksDestroy'])->name('api.v1.consumibles.inks.destroy');
    Route::get('/consumibles/tintas/{ink}/impresoras', [ConsumableApiController::class, 'inksPrinters'])->name('api.v1.consumibles.inks.printers');
    Route::get('/consumibles/tintas/{ink}/bitacora', [ConsumableApiController::class, 'inksAudit'])->name('api.v1.consumibles.inks.audit');

    Route::get('/consumibles/toner', [ConsumableApiController::class, 'tonerIndex'])->name('api.v1.consumibles.toner.index');
    Route::post('/consumibles/toner', [ConsumableApiController::class, 'tonerStore'])->name('api.v1.consumibles.toner.store');
    Route::get('/consumibles/toner/{toner}', [ConsumableApiController::class, 'tonerShow'])->name('api.v1.consumibles.toner.show');
    Route::put('/consumibles/toner/{toner}', [ConsumableApiController::class, 'tonerUpdate'])->name('api.v1.consumibles.toner.update');
    Route::delete('/consumibles/toner/{toner}', [ConsumableApiController::class, 'tonerDestroy'])->name('api.v1.consumibles.toner.destroy');
    Route::get('/consumibles/toner/{toner}/impresoras', [ConsumableApiController::class, 'tonerPrinters'])->name('api.v1.consumibles.toner.printers');
    Route::get('/consumibles/toner/{toner}/bitacora', [ConsumableApiController::class, 'tonerAudit'])->name('api.v1.consumibles.toner.audit');

    // Red REST API (Fase A: Dispositivos de Red)
    Route::get('/red/stats', [NetworkDeviceApiController::class, 'stats'])->name('api.v1.red.stats');
    Route::get('/red/dispositivos/{networkDevice}/bitacora', [NetworkDeviceApiController::class, 'auditLogs'])->name('api.v1.red.dispositivos.audit');
    Route::apiResource('/red/dispositivos', NetworkDeviceApiController::class)
        ->parameters(['dispositivos' => 'networkDevice'])
        ->names([
            'index' => 'api.v1.red.dispositivos.index',
            'store' => 'api.v1.red.dispositivos.store',
            'show' => 'api.v1.red.dispositivos.show',
            'update' => 'api.v1.red.dispositivos.update',
            'destroy' => 'api.v1.red.dispositivos.destroy',
        ]);

    // Red REST API (Fase B: WatchGuard / VPN)
    Route::get('/red/watchguard/{watchguardUser}/credencial', [WatchguardUserApiController::class, 'credential'])->name('api.v1.red.watchguard.credential');
    Route::get('/red/watchguard/{watchguardUser}/bitacora', [WatchguardUserApiController::class, 'auditLogs'])->name('api.v1.red.watchguard.audit');
    Route::apiResource('/red/watchguard', WatchguardUserApiController::class)
        ->parameters(['watchguard' => 'watchguardUser'])
        ->names([
            'index' => 'api.v1.red.watchguard.index',
            'store' => 'api.v1.red.watchguard.store',
            'show' => 'api.v1.red.watchguard.show',
            'update' => 'api.v1.red.watchguard.update',
            'destroy' => 'api.v1.red.watchguard.destroy',
        ]);

    // Red REST API (Fase C: Redes Empresariales)
    Route::get('/red/empresariales/{enterpriseNetwork}/credencial', [EnterpriseNetworkApiController::class, 'credential'])->name('api.v1.red.empresariales.credential');
    Route::get('/red/empresariales/{enterpriseNetwork}/bitacora', [EnterpriseNetworkApiController::class, 'auditLogs'])->name('api.v1.red.empresariales.audit');
    Route::apiResource('/red/empresariales', EnterpriseNetworkApiController::class)
        ->parameters(['empresariales' => 'enterpriseNetwork'])
        ->names([
            'index' => 'api.v1.red.empresariales.index',
            'store' => 'api.v1.red.empresariales.store',
            'show' => 'api.v1.red.empresariales.show',
            'update' => 'api.v1.red.empresariales.update',
            'destroy' => 'api.v1.red.empresariales.destroy',
        ]);

    // Archivos Generales REST API
    Route::get('/archivos-generales/{fileCatalog}/descargar', [FileCatalogApiController::class, 'download'])->name('api.v1.archivos-generales.download');
    Route::get('/archivos-generales/{fileCatalog}/vista', [FileCatalogApiController::class, 'preview'])->name('api.v1.archivos-generales.preview');

    Route::apiResource('archivos-generales', FileCatalogApiController::class)
        ->parameters(['archivos-generales' => 'fileCatalog'])
        ->names([
            'index' => 'api.v1.archivos-generales.index',
            'store' => 'api.v1.archivos-generales.store',
            'show' => 'api.v1.archivos-generales.show',
            'update' => 'api.v1.archivos-generales.update',
            'destroy' => 'api.v1.archivos-generales.destroy',
        ]);
    // Galería REST API
    Route::get('/galeria/{galleryItem}/imagen', [GalleryApiController::class, 'image'])->name('api.v1.gallery.image');
    Route::get('/galeria/{galleryItem}/thumbnail', [GalleryApiController::class, 'thumbnail'])->name('api.v1.gallery.thumbnail');
    Route::get('/galeria/{galleryItem}/descargar', [GalleryApiController::class, 'download'])->name('api.v1.gallery.download');

    Route::apiResource('galeria', GalleryApiController::class)
        ->parameters(['galeria' => 'galleryItem'])
        ->names([
            'index' => 'api.v1.gallery.index',
            'store' => 'api.v1.gallery.store',
            'show' => 'api.v1.gallery.show',
            'update' => 'api.v1.gallery.update',
            'destroy' => 'api.v1.gallery.destroy',
        ]);
    // Tutoriales REST API
    Route::get('/tutoriales/{tutorial}/ver', [TutorialApiController::class, 'preview'])->name('api.v1.tutorials.preview');
    Route::get('/tutoriales/{tutorial}/descargar', [TutorialApiController::class, 'download'])->name('api.v1.tutorials.download');

    Route::apiResource('tutoriales', TutorialApiController::class)
        ->parameters(['tutoriales' => 'tutorial'])
        ->names([
            'index' => 'api.v1.tutorials.index',
            'store' => 'api.v1.tutorials.store',
            'show' => 'api.v1.tutorials.show',
            'update' => 'api.v1.tutorials.update',
            'destroy' => 'api.v1.tutorials.destroy',
        ]);
    // Notas REST API
    Route::apiResource('notas', NoteApiController::class)
        ->parameters(['notas' => 'note'])
        ->names([
            'index' => 'api.v1.notes.index',
            'store' => 'api.v1.notes.store',
            'show' => 'api.v1.notes.show',
            'update' => 'api.v1.notes.update',
            'destroy' => 'api.v1.notes.destroy',
        ]);
    // Credenciales de Cuentas REST API
    Route::get('/credenciales-cuentas/{accountCredential}/secreto', [AccountCredentialApiController::class, 'secret'])
        ->name('api.v1.account-credentials.secret');
    Route::apiResource('credenciales-cuentas', AccountCredentialApiController::class)
        ->parameters(['credenciales-cuentas' => 'accountCredential'])
        ->names([
            'index' => 'api.v1.account-credentials.index',
            'store' => 'api.v1.account-credentials.store',
            'show' => 'api.v1.account-credentials.show',
            'update' => 'api.v1.account-credentials.update',
            'destroy' => 'api.v1.account-credentials.destroy',
        ]);
    // Correos Outlook REST API
    Route::get('/correos-outlook/{correos_outlook}/secreto', [OutlookAccountApiController::class, 'secret'])
        ->name('api.v1.outlook-accounts.secret');
    Route::apiResource('correos-outlook', OutlookAccountApiController::class)
        ->parameters(['correos-outlook' => 'correos_outlook'])
        ->names([
            'index' => 'api.v1.outlook-accounts.index',
            'store' => 'api.v1.outlook-accounts.store',
            'show' => 'api.v1.outlook-accounts.show',
            'update' => 'api.v1.outlook-accounts.update',
            'destroy' => 'api.v1.outlook-accounts.destroy',
        ]);
});
