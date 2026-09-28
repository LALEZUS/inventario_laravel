<?php

namespace App\Http\Controllers;

use App\Models\AssetFile;
use App\Models\Assignment;
use App\Models\HardwareAsset;
use App\Services\AssetQrCode;
use App\Services\AuditLogger;
use App\Services\ResponsivaGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AssetDocumentController extends Controller
{
    public function qr(Request $request, HardwareAsset $computer, AssetQrCode $qrCode): Response
    {
        $this->authorize('view', $computer);
        $png = $qrCode->png($computer);
        $headers = ['Content-Type' => 'image/png', 'Cache-Control' => 'private, no-store, max-age=0'];
        if ($request->boolean('download')) {
            $headers['Content-Disposition'] = 'attachment; filename="QR-'.$computer->id.'.png"';
        }
        return response($png, 200, $headers);
    }

    public function responsiva(
        Request $request,
        HardwareAsset $computer,
        ResponsivaGenerator $generator,
        AuditLogger $audit,
    ): Response {
        $this->authorize('upload', $computer);
        $assignment = $this->assignment($request, $computer);
        $withLetterhead = $request->input('letterhead', '1') !== '0';
        $photoLayout = $this->photoLayout($request);
        $computer->load(['employee', 'files']);
        $recipient = $assignment?->employee?->full_name ?: $assignment?->assigned_to ?: $computer->assigned_to ?: 'empleado';
        $pdf = $generator->generate($computer, $assignment, $withLetterhead, $photoLayout);
        $employee = Str::slug($recipient) ?: 'empleado';
        $variant = $withLetterhead ? 'Membretada' : 'Sin_membrete';
        $filename = 'Responsiva_'.$employee.'_'.$variant.'_'.now()->format('Y-m-d_His').'.pdf';
        $path = 'assets/inventory/'.$computer->id.'/'.$filename;

        Storage::disk('local')->put($path, $pdf);
        try {
            $file = AssetFile::create([
                'asset_type' => 'inventory',
                'asset_id' => $computer->id,
                'assignment_id' => $assignment?->id,
                'original_name' => $filename,
                'file_path' => 'laravel-local:'.$path,
                'file_type' => 'application/pdf',
                'file_size' => strlen($pdf),
                'label' => 'Responsiva firmable - '.($withLetterhead ? 'membretada' : 'sin membrete'),
                'comments' => 'Generada desde Laravel con el formato de carta responsiva',
            ]);
            $audit->record('generate_responsiva', 'hardware_assets', $computer->id, null, [
                'asset_file_id' => $file->id,
                'filename' => $filename,
                'letterhead' => $withLetterhead,
                'photo_layout' => $photoLayout,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function responsivaPreview(
        Request $request,
        HardwareAsset $computer,
        ResponsivaGenerator $generator,
    ): Response {
        $this->authorize('upload', $computer);
        $assignment = $this->assignment($request, $computer);
        $withLetterhead = $request->input('letterhead', '1') !== '0';
        $photoLayout = $this->photoLayout($request);
        $pdf = $generator->generate($computer, $assignment, $withLetterhead, $photoLayout);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Vista_previa_responsiva.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function assignment(Request $request, HardwareAsset $computer): ?Assignment
    {
        if (! $request->filled('assignment_id')) {
            return null;
        }

        return Assignment::where('asset_type', 'inventory')
            ->where('asset_id', $computer->id)
            ->with('employee')
            ->findOrFail($request->integer('assignment_id'));
    }

    private function photoLayout(Request $request): string
    {
        $layout = (string) $request->input('photo_layout', 'balanced');

        return in_array($layout, ['balanced', 'large'], true) ? $layout : 'balanced';
    }
}
