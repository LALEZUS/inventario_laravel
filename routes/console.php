<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schedule;
use App\Services\DatabaseBackup;
use App\Services\MigrationReadiness;
use App\Models\FileCatalog;
use App\Models\BackupRun;
use App\Models\GalleryItem;
use App\Models\Tutorial;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('inventory:import-library', function () {
    $legacy = realpath((string) config('inventory.legacy_files_root'));
    if ($legacy === false) { $this->error('No se encontro el inventario anterior.'); return 1; }
    foreach (Tutorial::whereNotNull('content_url')->get() as $item) {
        if (str_starts_with($item->content_url, 'laravel-local:')) continue;
        $source = $legacy.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $item->content_url);
        if (!is_file($source)) { $this->warn('PDF faltante: '.$item->title); continue; }
        $path = 'library/imported/tutorials/'.basename($source); Storage::disk('local')->put($path, File::get($source)); $item->update(['content_url'=>'laravel-local:'.$path]);
    }
    foreach (GalleryItem::all() as $item) {
        if (str_starts_with($item->filename, 'laravel-local:')) continue;
        $source = $legacy.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'gallery'.DIRECTORY_SEPARATOR.basename($item->filename);
        if (!is_file($source)) { $this->warn('Imagen faltante: '.$item->title); continue; }
        $path='library/imported/gallery/'.basename($source); Storage::disk('local')->put($path,File::get($source)); $item->update(['filename'=>'laravel-local:'.$path]);
    }
    foreach (File::files($legacy.DIRECTORY_SEPARATOR.'ftp_files') as $file) {
        if ($file->getFilename()==='file_aliases.json') continue;
        $path='library/imported/files/'.$file->getFilename(); if(!Storage::disk('local')->exists($path)) Storage::disk('local')->put($path,File::get($file->getPathname()));
        FileCatalog::firstOrCreate(['original_name'=>$file->getFilename()],['file_path'=>'laravel-local:'.$path,'file_size'=>$file->getSize(),'alias_name'=>$file->getFilename()]);
    }
    foreach (BackupRun::all() as $run) {
        if (str_starts_with($run->filename, 'laravel-local:')) continue;
        $source=$legacy.DIRECTORY_SEPARATOR.'db_backups'.DIRECTORY_SEPARATOR.basename($run->filename);
        if(!is_file($source)){ $this->warn('Respaldo faltante: '.$run->filename); continue; }
        $path='backups/imported/'.basename($source); Storage::disk('local')->put($path,File::get($source)); $run->update(['filename'=>'laravel-local:'.$path,'file_size'=>File::size($source)]);
    }
    $this->info('Biblioteca historica importada.'); return 0;
})->purpose('Copia tutoriales, galeria y archivos heredados al almacenamiento privado');

Artisan::command('inventory:monthly-backup', function (DatabaseBackup $backup) {
    $run=$backup->create('monthly'); $this->info('Respaldo mensual listo: '.$run->period_key);
})->purpose('Genera una sola copia mensual de la base de datos');

Schedule::command('inventory:monthly-backup')->monthlyOn(1, '02:00')->withoutOverlapping();

Artisan::command('inventory:readiness {--json}', function (MigrationReadiness $readiness) {
    $report = $readiness->audit(DB::connection('legacy'), DB::connection());

    if ($this->option('json')) {
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $report['ready'] ? 0 : 2;
    }

    $this->table(
        ['Tabla', 'Anterior', 'Laravel', 'Solo anterior', 'Solo Laravel', 'Diferentes', 'Estado'],
        collect($report['tables'])->map(fn (array $table) => [
            $table['table'],
            $table['source_count'] ?? 'Falta',
            $table['target_count'] ?? 'Falta',
            count($table['source_only']),
            count($table['target_only']),
            count($table['changed']),
            $table['ready'] ? 'Lista' : 'Revisar',
        ])->all(),
    );

    $this->{$report['ready'] ? 'info' : 'warn'}($report['ready']
        ? 'Las tablas auditadas estan listas para el corte.'
        : 'Hay diferencias que deben revisarse antes del corte. No se modifico ninguna base.');

    return $report['ready'] ? 0 : 2;
})->purpose('Compara el inventario anterior con Laravel sin modificar datos');
