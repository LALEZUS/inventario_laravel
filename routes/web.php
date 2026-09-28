<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\AssetFileController;
use App\Http\Controllers\AssetDocumentController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\CellphoneController;
use App\Http\Controllers\CellphoneAssignmentController;
use App\Http\Controllers\CalendarReminderController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\AccountCredentialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmailBackupController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EnterpriseNetworkController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HardwareAssetController;
use App\Http\Controllers\ComputerSetupGuideController;
use App\Http\Controllers\MaintenanceLogController;
use App\Http\Controllers\InkController;
use App\Http\Controllers\InventoryEventController;
use App\Http\Controllers\InventoryExportController;
use App\Http\Controllers\BulkActionController;
use App\Http\Controllers\FileCatalogController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\MicrosoftEmailController;
use App\Http\Controllers\NetworkDeviceController;
use App\Http\Controllers\OutlookAccountController;
use App\Http\Controllers\OfficeEmailController;
use App\Http\Controllers\PeripheralController;
use App\Http\Controllers\PrintSupplyController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\SoftwareLicenseController;
use App\Http\Controllers\SensitiveValueController;
use App\Http\Controllers\TonerController;
use App\Http\Controllers\TutorialController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WatchguardUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/buscar', [GlobalSearchController::class, 'index'])->name('search.index');
    Route::get('/buscar/sugerencias', [GlobalSearchController::class, 'suggestions'])->name('search.suggestions');
    Route::get('/eventos/inventario', InventoryEventController::class)->name('inventory-events.stream');
    Route::get('/eventos/inventario/poll', [InventoryEventController::class, 'poll'])->name('inventory-events.poll');
    Route::get('/exportar/{dataset}', [InventoryExportController::class, 'create'])->name('inventory-export.create');
    Route::post('/exportar/{dataset}', [InventoryExportController::class, 'download'])->name('inventory-export.download');
    Route::get('/gestion-masiva/{dataset}', [BulkActionController::class, 'index'])->name('bulk.index');
    Route::put('/gestion-masiva/{dataset}', [BulkActionController::class, 'update'])->name('bulk.update');
    Route::get('/computadoras', [HardwareAssetController::class, 'index'])->name('computers.index');
    Route::get('/computadoras/nueva', [HardwareAssetController::class, 'create'])->name('computers.create');
    Route::get('/computadoras/guia-inicio', ComputerSetupGuideController::class)->name('computers.setup-guide');
    Route::post('/computadoras', [HardwareAssetController::class, 'store'])->name('computers.store');
    Route::post('/computadoras/nfo/analizar', [HardwareAssetController::class, 'parseNfo'])->name('computers.nfo.parse');
    Route::get('/computadoras/{computer}/secretos', [SensitiveValueController::class, 'computer'])->name('computers.secrets');
    Route::get('/computadoras/{computer}', [HardwareAssetController::class, 'show'])->name('computers.show');
    Route::get('/computadoras/{computer}/editar', [HardwareAssetController::class, 'edit'])->name('computers.edit');
    Route::put('/computadoras/{computer}', [HardwareAssetController::class, 'update'])->name('computers.update');
    Route::delete('/computadoras/{computer}', [HardwareAssetController::class, 'destroy'])->name('computers.destroy');
    Route::get('/computadoras/{computer}/nfo', [HardwareAssetController::class, 'downloadNfo'])->name('computers.nfo.download');
    Route::get('/computadoras/{computer}/qr', [AssetDocumentController::class, 'qr'])->name('computers.qr');
    Route::post('/computadoras/{computer}/responsiva/vista-previa', [AssetDocumentController::class, 'responsivaPreview'])->name('computers.responsiva.preview');
    Route::post('/computadoras/{computer}/responsiva', [AssetDocumentController::class, 'responsiva'])->name('computers.responsiva');
    Route::post('/computadoras/{computer}/asignaciones', [AssignmentController::class, 'store'])->name('computers.assignments.store');
    Route::put('/computadoras/{computer}/asignaciones/{assignment}/devolver', [AssignmentController::class, 'return'])->name('computers.assignments.return');
    Route::delete('/computadoras/{computer}/asignaciones/{assignment}', [AssignmentController::class, 'destroy'])->name('computers.assignments.destroy');
    Route::post('/computadoras/{computer}/mantenimientos', [MaintenanceLogController::class, 'store'])->name('computers.maintenance.store');
    Route::put('/computadoras/{computer}/mantenimientos/{maintenance}', [MaintenanceLogController::class, 'update'])->name('computers.maintenance.update');
    Route::delete('/computadoras/{computer}/mantenimientos/{maintenance}', [MaintenanceLogController::class, 'destroy'])->name('computers.maintenance.destroy');

    Route::get('/celulares', [CellphoneController::class, 'index'])->name('cellphones.index');
    Route::get('/celulares/nuevo', [CellphoneController::class, 'create'])->name('cellphones.create');
    Route::post('/celulares', [CellphoneController::class, 'store'])->name('cellphones.store');
    Route::get('/celulares/{cellphone}/secretos', [SensitiveValueController::class, 'cellphone'])->name('cellphones.secrets');
    Route::get('/celulares/{cellphone}', [CellphoneController::class, 'show'])->name('cellphones.show');
    Route::get('/celulares/{cellphone}/editar', [CellphoneController::class, 'edit'])->name('cellphones.edit');
    Route::put('/celulares/{cellphone}', [CellphoneController::class, 'update'])->name('cellphones.update');
    Route::delete('/celulares/{cellphone}', [CellphoneController::class, 'destroy'])->name('cellphones.destroy');
    Route::get('/celulares/{cellphone}/compartir', [CellphoneController::class, 'share'])->name('cellphones.share');
    Route::post('/celulares/{cellphone}/asignaciones', [CellphoneAssignmentController::class, 'store'])->name('cellphones.assignments.store');

    Route::get('/perifericos', [PeripheralController::class, 'index'])->name('peripherals.index');
    Route::get('/perifericos/nuevo', [PeripheralController::class, 'create'])->name('peripherals.create');
    Route::post('/perifericos', [PeripheralController::class, 'store'])->name('peripherals.store');
    Route::get('/perifericos/{peripheral}', [PeripheralController::class, 'show'])->name('peripherals.show');
    Route::get('/perifericos/{peripheral}/editar', [PeripheralController::class, 'edit'])->name('peripherals.edit');
    Route::put('/perifericos/{peripheral}', [PeripheralController::class, 'update'])->name('peripherals.update');
    Route::delete('/perifericos/{peripheral}', [PeripheralController::class, 'destroy'])->name('peripherals.destroy');

    Route::get('/impresoras', [PrinterController::class, 'index'])->name('printers.index');
    Route::get('/impresoras/nueva', [PrinterController::class, 'create'])->name('printers.create');
    Route::post('/impresoras', [PrinterController::class, 'store'])->name('printers.store');
    Route::get('/impresoras/{printer}', [PrinterController::class, 'show'])->name('printers.show');
    Route::get('/impresoras/{printer}/editar', [PrinterController::class, 'edit'])->name('printers.edit');
    Route::put('/impresoras/{printer}', [PrinterController::class, 'update'])->name('printers.update');
    Route::delete('/impresoras/{printer}', [PrinterController::class, 'destroy'])->name('printers.destroy');

    Route::get('/consumibles', PrintSupplyController::class)->name('supplies.index');
    Route::get('/consumibles/tintas/nueva', [InkController::class, 'create'])->name('inks.create');
    Route::post('/consumibles/tintas', [InkController::class, 'store'])->name('inks.store');
    Route::get('/consumibles/tintas/{ink}', [InkController::class, 'show'])->name('inks.show');
    Route::get('/consumibles/tintas/{ink}/editar', [InkController::class, 'edit'])->name('inks.edit');
    Route::patch('/consumibles/tintas/{ink}/cantidad', [InkController::class, 'updateQuantity'])->name('inks.quantity.update');
    Route::put('/consumibles/tintas/{ink}', [InkController::class, 'update'])->name('inks.update');
    Route::delete('/consumibles/tintas/{ink}', [InkController::class, 'destroy'])->name('inks.destroy');
    Route::get('/consumibles/toner/nuevo', [TonerController::class, 'create'])->name('toner.create');
    Route::post('/consumibles/toner', [TonerController::class, 'store'])->name('toner.store');
    Route::get('/consumibles/toner/{toner}', [TonerController::class, 'show'])->name('toner.show');
    Route::get('/consumibles/toner/{toner}/editar', [TonerController::class, 'edit'])->name('toner.edit');
    Route::patch('/consumibles/toner/{toner}/cantidad', [TonerController::class, 'updateQuantity'])->name('toner.quantity.update');
    Route::put('/consumibles/toner/{toner}', [TonerController::class, 'update'])->name('toner.update');
    Route::delete('/consumibles/toner/{toner}', [TonerController::class, 'destroy'])->name('toner.destroy');

    Route::get('/red', NetworkController::class)->name('network.index');
    Route::get('/red/uso-adecuado', [NetworkController::class, 'acceptableUse'])->name('network.acceptable-use.preview');
    Route::get('/red/uso-adecuado-descargar', [NetworkController::class, 'acceptableUse'])->name('network.acceptable-use.download');
    Route::get('/red/dispositivos/nuevo', [NetworkDeviceController::class, 'create'])->name('network-devices.create');
    Route::post('/red/dispositivos', [NetworkDeviceController::class, 'store'])->name('network-devices.store');
    Route::get('/red/dispositivos/{networkDevice}', [NetworkDeviceController::class, 'show'])->name('network-devices.show');
    Route::get('/red/dispositivos/{networkDevice}/editar', [NetworkDeviceController::class, 'edit'])->name('network-devices.edit');
    Route::put('/red/dispositivos/{networkDevice}', [NetworkDeviceController::class, 'update'])->name('network-devices.update');
    Route::delete('/red/dispositivos/{networkDevice}', [NetworkDeviceController::class, 'destroy'])->name('network-devices.destroy');
    Route::get('/red/watchguard/nuevo', [WatchguardUserController::class, 'create'])->name('watchguard-users.create');
    Route::post('/red/watchguard', [WatchguardUserController::class, 'store'])->name('watchguard-users.store');
    Route::get('/red/watchguard/{watchguardUser}', [WatchguardUserController::class, 'show'])->name('watchguard-users.show');
    Route::get('/red/watchguard/{watchguardUser}/editar', [WatchguardUserController::class, 'edit'])->name('watchguard-users.edit');
    Route::put('/red/watchguard/{watchguardUser}', [WatchguardUserController::class, 'update'])->name('watchguard-users.update');
    Route::delete('/red/watchguard/{watchguardUser}', [WatchguardUserController::class, 'destroy'])->name('watchguard-users.destroy');
    Route::get('/secretos/red/watchguard/{watchguardUser}', [SensitiveValueController::class, 'watchguard'])->name('watchguard-users.secrets');
    Route::get('/red/empresariales/nueva', [EnterpriseNetworkController::class, 'create'])->name('enterprise-networks.create');
    Route::post('/red/empresariales', [EnterpriseNetworkController::class, 'store'])->name('enterprise-networks.store');
    Route::get('/red/empresariales/{enterpriseNetwork}', [EnterpriseNetworkController::class, 'show'])->name('enterprise-networks.show');
    Route::get('/red/empresariales/{enterpriseNetwork}/editar', [EnterpriseNetworkController::class, 'edit'])->name('enterprise-networks.edit');
    Route::put('/red/empresariales/{enterpriseNetwork}', [EnterpriseNetworkController::class, 'update'])->name('enterprise-networks.update');
    Route::delete('/red/empresariales/{enterpriseNetwork}', [EnterpriseNetworkController::class, 'destroy'])->name('enterprise-networks.destroy');
    Route::get('/secretos/red/empresariales/{enterpriseNetwork}', [SensitiveValueController::class, 'enterpriseNetwork'])->name('enterprise-networks.secrets');

    Route::get('/credenciales', CredentialController::class)->name('credentials.index');
    Route::resource('/credenciales/cuentas', AccountCredentialController::class)
        ->parameters(['cuentas' => 'accountCredential'])->names('account-credentials')->except('index');
    Route::resource('/credenciales/outlook', OutlookAccountController::class)
        ->parameters(['outlook' => 'outlookAccount'])->names('outlook-accounts')->except('index');
    Route::resource('/credenciales/licencias', SoftwareLicenseController::class)
        ->parameters(['licencias' => 'softwareLicense'])->names('software-licenses')->except('index');
    Route::get('/secretos/cuentas/{accountCredential}', [SensitiveValueController::class, 'account'])->name('account-credentials.secrets');
    Route::get('/secretos/outlook/{outlookAccount}', [SensitiveValueController::class, 'outlook'])->name('outlook-accounts.secrets');
    Route::get('/secretos/licencias/{softwareLicense}', [SensitiveValueController::class, 'license'])->name('software-licenses.secrets');

    Route::get('/correos', EmailController::class)->name('emails.index');
    Route::resource('/correos/microsoft-365', MicrosoftEmailController::class)
        ->parameters(['microsoft-365' => 'microsoftEmail'])->names('microsoft-emails')->except('index');
    Route::resource('/correos/windows', OfficeEmailController::class)
        ->parameters(['windows' => 'officeEmail'])->names('office-emails')->except('index');
    Route::get('/secretos/correos/microsoft/{microsoftEmail}', [SensitiveValueController::class, 'microsoftEmail'])->name('microsoft-emails.secrets');
    Route::get('/secretos/correos/windows/{officeEmail}', [SensitiveValueController::class, 'officeEmail'])->name('office-emails.secrets');
    Route::resource('/correos/reenvios', EmailBackupController::class)
        ->parameters(['reenvios' => 'emailBackup'])->names('email-backups')->except('index');

    Route::get('/tutoriales/{tutorial}/ver', [TutorialController::class, 'preview'])->name('tutorials.preview');
    Route::get('/tutoriales/{tutorial}/descargar', [TutorialController::class, 'download'])->name('tutorials.download');
    Route::resource('/tutoriales', TutorialController::class)->parameters(['tutoriales'=>'tutorial'])->names('tutorials');
    Route::get('/galeria/{galleryItem}/imagen', [GalleryController::class, 'image'])->name('gallery.image');
    Route::resource('/galeria', GalleryController::class)->parameters(['galeria'=>'galleryItem'])->names('gallery');
    Route::get('/archivos-generales/{fileCatalog}/descargar', [FileCatalogController::class, 'download'])->name('files.download');
    Route::get('/archivos-generales/{fileCatalog}/vista', [FileCatalogController::class, 'preview'])->name('files.preview');
    Route::resource('/archivos-generales', FileCatalogController::class)->parameters(['archivos-generales'=>'fileCatalog'])->names('files');
    Route::resource('/notas', NoteController::class)->parameters(['notas'=>'note'])->names('notes');
    Route::get('/agenda', [CalendarReminderController::class,'index'])->name('calendar.index');
    Route::get('/agenda/nuevo', [CalendarReminderController::class,'create'])->name('calendar.create');
    Route::post('/agenda', [CalendarReminderController::class,'store'])->name('calendar.store');
    Route::get('/agenda/{calendarReminder}/editar', [CalendarReminderController::class,'edit'])->name('calendar.edit');
    Route::put('/agenda/{calendarReminder}', [CalendarReminderController::class,'update'])->name('calendar.update');
    Route::delete('/agenda/{calendarReminder}', [CalendarReminderController::class,'destroy'])->name('calendar.destroy');
    Route::get('/respaldos', [BackupController::class,'index'])->name('backups.index');
    Route::post('/respaldos', [BackupController::class,'store'])->name('backups.store');
    Route::get('/respaldos/{backupRun}/descargar', [BackupController::class,'download'])->name('backups.download');

    Route::get('/empleados', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/empleados/nuevo', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/empleados', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/empleados/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('/empleados/{employee}/editar', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/empleados/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/empleados/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

    Route::resource('/usuarios', UserController::class)->parameters(['usuarios' => 'user'])->names('users');

    Route::post('/archivos/{assetType}/{assetId}', [AssetFileController::class, 'store'])->whereNumber('assetId')->name('asset-files.store');
    Route::post('/archivos/{assetType}/{assetId}/fotos', [AssetFileController::class, 'storePhotos'])->whereNumber('assetId')->name('asset-files.photos.store');
    Route::get('/archivos/{file}/descargar', [AssetFileController::class, 'download'])->name('asset-files.download');
    Route::get('/archivos/{file}/vista', [AssetFileController::class, 'preview'])->name('asset-files.preview');
    Route::put('/archivos/{file}/etiqueta', [AssetFileController::class, 'updateLabel'])->name('asset-files.label.update');
    Route::delete('/archivos/{file}', [AssetFileController::class, 'destroy'])->name('asset-files.destroy');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
