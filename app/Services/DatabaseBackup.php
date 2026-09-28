<?php

namespace App\Services;

use App\Models\BackupRun;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DatabaseBackup
{
    public function create(string $type = 'manual'): BackupRun
    {
        $period = $type === 'monthly' ? now()->format('Y-m') : now()->format('Y-m-d_H-i-s');
        if ($type === 'monthly' && ($existing = BackupRun::where(['backup_type' => $type, 'period_key' => $period])->first())) {
            return $existing;
        }

        $binary = (string) config('inventory.mysqldump_path');
        if (!is_file($binary) || !is_executable($binary)) {
            throw new RuntimeException('No se encontro mysqldump. Configura INVENTORY_MYSQLDUMP_PATH.');
        }

        $result = Process::env(['MYSQL_PWD' => (string) config('database.connections.mysql.password')])
            ->timeout(300)
            ->run([
                $binary,
                '--host=' . config('database.connections.mysql.host'),
                '--port=' . config('database.connections.mysql.port'),
                '--user=' . config('database.connections.mysql.username'),
                '--single-transaction',
                '--routines',
                '--events',
                (string) config('database.connections.mysql.database'),
            ]);

        if ($result->failed() || trim($result->output()) === '') {
            throw new RuntimeException('No se pudo generar el respaldo: ' . ($result->errorOutput() ?: 'mysqldump no devolvio datos.'));
        }

        $path = 'backups/inventario_laravel_' . now()->format('Ymd_His') . '.sql';
        if (!Storage::disk('local')->put($path, $result->output())) {
            throw new RuntimeException('No se pudo guardar el archivo de respaldo.');
        }

        $run = BackupRun::updateOrCreate(
            ['backup_type' => $type, 'period_key' => $period],
            ['filename' => 'laravel-local:' . $path, 'file_size' => Storage::disk('local')->size($path)]
        );

        $this->pruneLocalBackups();

        return $run;
    }

    public function path(BackupRun $run): string
    {
        if (str_starts_with($run->filename, 'laravel-local:')) {
            $path = substr($run->filename, 14);
            abort_unless(Storage::disk('local')->exists($path), 404);
            return Storage::disk('local')->path($path);
        }

        $root = realpath(rtrim((string) config('inventory.legacy_files_root'), '/\\') . DIRECTORY_SEPARATOR . 'db_backups');
        $path = $root ? realpath($root . DIRECTORY_SEPARATOR . basename($run->filename)) : false;
        abort_unless($path && str_starts_with($path, $root . DIRECTORY_SEPARATOR), 404);

        return $path;
    }

    private function pruneLocalBackups(): void
    {
        $disk = Storage::disk('local');
        $files = collect($disk->files('backups'))
            ->filter(fn (string $file): bool => str_ends_with($file, '.sql'))
            ->sortByDesc(fn (string $file): int => $disk->lastModified($file));
        $maxFiles = max(1, (int) config('inventory.backup_max_local_files', 50));
        $cutoff = now()->subDays(max(1, (int) config('inventory.backup_retention_days', 365)))->timestamp;

        $files->each(function (string $file, int $index) use ($disk, $cutoff, $maxFiles): void {
            if ($index >= $maxFiles || $disk->lastModified($file) < $cutoff) {
                $disk->delete($file);
                BackupRun::where('filename', 'laravel-local:' . $file)->delete();
            }
        });
    }
}
