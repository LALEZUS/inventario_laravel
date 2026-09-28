<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AssetFile;
use App\Models\Cellphone;
use App\Models\HardwareAsset;
use App\Models\Peripheral;
use App\Models\Printer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetFileApiController extends Controller
{
    private const TYPES = [
        'cellphone' => Cellphone::class,
        'inventory' => HardwareAsset::class,
        'peripheral' => Peripheral::class,
        'printer' => Printer::class,
    ];

    public function preview(Request $request, AssetFile $file): BinaryFileResponse
    {
        $asset = $this->resolveAsset($file->asset_type, (int) $file->asset_id);
        $this->authorize('view', $asset);
        abort_unless($file->isPreviewable(), 404);

        if (str_starts_with((string) $file->file_path, 'laravel-local:')) {
            $path = substr($file->file_path, strlen('laravel-local:'));
            abort_unless(Storage::disk('local')->exists($path), 404);
            return response()->file(Storage::disk('local')->path($path), [
                'Content-Type' => $file->previewMimeType(),
                'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"',
            ]);
        }

        $legacyPath = $this->legacyPath($file->file_path);
        abort_unless(is_file($legacyPath), 404);
        return response()->file($legacyPath, [
            'Content-Type' => $file->previewMimeType(),
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"',
        ]);
    }

    public function download(Request $request, AssetFile $file): BinaryFileResponse
    {
        $asset = $this->resolveAsset($file->asset_type, (int) $file->asset_id);
        $this->authorize('view', $asset);

        if (str_starts_with((string) $file->file_path, 'laravel-local:')) {
            $path = substr($file->file_path, strlen('laravel-local:'));
            abort_unless(Storage::disk('local')->exists($path), 404);
            return response()->download(Storage::disk('local')->path($path), $file->original_name);
        }

        $legacyPath = $this->legacyPath($file->file_path);
        abort_unless(is_file($legacyPath), 404);
        return response()->download($legacyPath, $file->original_name);
    }

    private function resolveAsset(string $assetType, int $assetId): Model
    {
        abort_unless(isset(self::TYPES[$assetType]), 404);
        return self::TYPES[$assetType]::findOrFail($assetId);
    }

    private function legacyPath(string $storedPath): string
    {
        $root = realpath((string) config('inventory.legacy_files_root'));
        abort_unless($root !== false, 404);
        $candidate = realpath($root.DIRECTORY_SEPARATOR.ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath), DIRECTORY_SEPARATOR));
        abort_unless($candidate !== false && str_starts_with($candidate, $root.DIRECTORY_SEPARATOR), 404);
        return $candidate;
    }
}
