<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class OptimizeImages extends Command
{
    protected $signature = 'inventory:optimize-images {path? : Carpeta de imagenes, por defecto public/images}';
    protected $description = 'Genera derivados WebP/AVIF sin modificar las imagenes originales';

    public function handle(): int
    {
        $root = base_path($this->argument('path') ?: env('INVENTORY_IMAGE_SOURCE', 'public/images'));
        if (! is_dir($root)) {
            $this->error("No existe la carpeta: {$root}");
            return self::FAILURE;
        }

        $webp = function_exists('imagewebp');
        $avif = function_exists('imageavif');
        if (! $webp && ! $avif) {
            $this->warn('PHP no tiene GD/AVIF habilitado; no se generaron derivados. La aplicacion seguira usando los originales.');
            return self::SUCCESS;
        }

        $quality = max(1, min(100, (int) env('INVENTORY_IMAGE_QUALITY', 82)));
        $count = 0;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'], true)) continue;
            $source = $file->getPathname();
            $image = @imagecreatefromstring((string) file_get_contents($source));
            if (! $image) continue;
            imagealphablending($image, true);
            imagesavealpha($image, true);
            $base = substr($source, 0, -strlen($file->getExtension()) - 1);
            if ($webp && @imagewebp($image, $base . '.webp', $quality)) $count++;
            if ($avif && @imageavif($image, $base . '.avif', $quality)) $count++;
            imagedestroy($image);
        }
        $this->info("Derivados generados: {$count}");
        return self::SUCCESS;
    }
}
