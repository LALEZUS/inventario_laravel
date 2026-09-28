<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
class LibraryFile
{
    public function path(string $stored,string $legacyFolder): string {
        if(str_starts_with($stored,'laravel-local:')){$path=substr($stored,14);abort_unless(Storage::disk('local')->exists($path),404);return Storage::disk('local')->path($path);}
        $root=realpath(rtrim((string)config('inventory.legacy_files_root'),'/\\').DIRECTORY_SEPARATOR.$legacyFolder);abort_unless($root!==false,404);$candidate=realpath($root.DIRECTORY_SEPARATOR.basename($stored));abort_unless($candidate!==false&&str_starts_with($candidate,$root.DIRECTORY_SEPARATOR),404);return $candidate;
    }
    public function delete(string $stored,string $legacyFolder): void { if(str_starts_with($stored,'laravel-local:'))Storage::disk('local')->delete(substr($stored,14));else{$path=$this->path($stored,$legacyFolder);if(is_file($path))unlink($path);} }
}
