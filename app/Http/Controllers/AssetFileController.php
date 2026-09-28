<?php

namespace App\Http\Controllers;

use App\Models\AssetFile;
use App\Models\Cellphone;
use App\Models\HardwareAsset;
use App\Models\Peripheral;
use App\Models\Printer;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetFileController extends Controller
{
    private const TYPES = [
        'cellphone' => Cellphone::class,
        'inventory' => HardwareAsset::class,
        'peripheral' => Peripheral::class,
        'printer' => Printer::class,
    ];

    public function store(Request $request, string $assetType, int $assetId, AuditLogger $audit): RedirectResponse
    {
        $asset = $this->resolveAsset($assetType, $assetId);
        $this->authorize('upload', $asset);
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:15360', 'extensions:pdf,jpg,jpeg,png,webp,txt,nfo,doc,docx,xls,xlsx,csv'],
            'label' => ['nullable', 'string', 'max:120'],
            'comments' => ['nullable', 'string'],
        ]);
        $upload = $request->file('file');
        $base = Str::slug(pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'archivo';
        $name = now()->format('YmdHis').'-'.Str::uuid().'-'.$base.'.'.strtolower($upload->getClientOriginalExtension());
        $path = $upload->storeAs("assets/{$assetType}/{$assetId}", $name, 'local');
        $file = AssetFile::create([
            'asset_type' => $assetType,
            'asset_id' => $assetId,
            'original_name' => $upload->getClientOriginalName(),
            'file_path' => 'laravel-local:'.$path,
            'file_type' => $upload->getMimeType(),
            'file_size' => $upload->getSize(),
            'label' => $validated['label'] ?? null,
            'comments' => $validated['comments'] ?? null,
        ]);
        $audit->record('upload', 'asset_files', $file->id, null, $file->getAttributes());

        return back()->with('success', 'Archivo adjunto correctamente.');
    }

    public function download(AssetFile $file): BinaryFileResponse
    {
        $asset = $this->resolveAsset($file->asset_type, (int) $file->asset_id);
        $this->authorize('view', $asset);

        if (str_starts_with($file->file_path, 'laravel-local:')) {
            $path = substr($file->file_path, strlen('laravel-local:'));
            abort_unless(Storage::disk('local')->exists($path), 404);
            return response()->download(Storage::disk('local')->path($path), $file->original_name);
        }

        $legacyPath = $this->legacyPath($file->file_path);
        abort_unless(is_file($legacyPath), 404);
        return response()->download($legacyPath, $file->original_name);
    }

    public function preview(AssetFile $file): BinaryFileResponse
    {
        $asset = $this->resolveAsset($file->asset_type, (int) $file->asset_id);
        $this->authorize('view', $asset);
        abort_unless($this->isPreviewable($file), 404);

        if (str_starts_with($file->file_path, 'laravel-local:')) {
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

    public function storePhotos(Request $request, string $assetType, int $assetId, AuditLogger $audit): RedirectResponse
    {
        $asset = $this->resolveAsset($assetType, $assetId);
        $this->authorize('upload', $asset);
        $validated = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:12'],
            'photos.*' => ['required', 'file', 'max:15360', 'mimes:jpg,jpeg,png,webp'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        foreach ($request->file('photos', []) as $upload) {
            $base = Str::slug(pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'foto';
            $name = now()->format('YmdHis').'-'.Str::uuid().'-'.$base.'.'.strtolower($upload->getClientOriginalExtension());
            $path = $upload->storeAs("assets/{$assetType}/{$assetId}", $name, 'local');
            $file = AssetFile::create([
                'asset_type' => $assetType,
                'asset_id' => $assetId,
                'original_name' => $upload->getClientOriginalName(),
                'file_path' => 'laravel-local:'.$path,
                'file_type' => $upload->getMimeType(),
                'file_size' => $upload->getSize(),
                'label' => $validated['label'] ?? null,
            ]);
            $audit->record('upload', 'asset_files', $file->id, null, $file->getAttributes());
        }

        return back()->with('success', count($request->file('photos', [])) === 1 ? 'Foto adjuntada correctamente.' : 'Fotos adjuntadas correctamente.');
    }

    public function updateLabel(Request $request, AssetFile $file, AuditLogger $audit): RedirectResponse
    {
        $asset = $this->resolveAsset($file->asset_type, (int) $file->asset_id);
        $this->authorize('update', $asset);
        abort_unless($file->isImage(), 404);

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
        ]);
        $before = $file->getAttributes();
        $file->update(['label' => filled($validated['label'] ?? null) ? trim($validated['label']) : null]);
        $audit->record('update', 'asset_files', $file->id, $before, $file->fresh()->getAttributes());

        return back()->with('success', 'Etiqueta de la foto actualizada correctamente.');
    }

    public function destroy(AssetFile $file, AuditLogger $audit): RedirectResponse
    {
        $asset = $this->resolveAsset($file->asset_type, (int) $file->asset_id);
        $this->authorize('delete', $asset);
        $before = $file->getAttributes();
        if (str_starts_with($file->file_path, 'laravel-local:')) {
            Storage::disk('local')->delete(substr($file->file_path, strlen('laravel-local:')));
        } else {
            $legacyPath = $this->legacyPath($file->file_path);
            if (is_file($legacyPath)) unlink($legacyPath);
        }
        $file->delete();
        $audit->record('delete', 'asset_files', $file->id, $before, null);
        return back()->with('success', 'Archivo eliminado correctamente.');
    }

    private function resolveAsset(string $assetType, int $assetId): Model
    {
        abort_unless(isset(self::TYPES[$assetType]), 404);
        return self::TYPES[$assetType]::findOrFail($assetId);
    }

    private function isPreviewable(AssetFile $file): bool
    {
        return $file->isPreviewable();
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
