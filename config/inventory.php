<?php

return [
    'export_max_rows' => (int) env('INVENTORY_EXPORT_MAX_ROWS', 10000),
    'mysqldump_path' => env('INVENTORY_MYSQLDUMP_PATH', 'C:\\xampp\\mysql\\bin\\mysqldump.exe'),
    'backup_retention_days' => (int) env('INVENTORY_BACKUP_RETENTION_DAYS', 365),
    'backup_max_local_files' => (int) env('INVENTORY_BACKUP_MAX_LOCAL_FILES', 50),
    'legacy_files_root' => env('LEGACY_FILES_ROOT', base_path('../inventario')),
    'catalogs' => [
        'license_types' => ['Licencia', 'Credencial', 'Suscripcion', 'Certificado', 'Otro'],
        'license_statuses' => ['Activa', 'Por vencer', 'Vencida', 'Suspendida', 'Baja'],
        'account_statuses' => ['Activo', 'Inactivo', 'Suspendido', 'Perdido', 'Baja'],
        'email_statuses' => ['ACTIVA', 'INACTIVA', 'SUSPENDIDA', 'PERDIDA', 'BAJA'],
    ],
];
