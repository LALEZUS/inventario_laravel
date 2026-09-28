<?php

namespace App\Services;

use App\Models\AssetFile;
use App\Models\Assignment;
use App\Models\HardwareAsset;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class ResponsivaGenerator
{
    public function generate(
        HardwareAsset $computer,
        ?Assignment $assignment = null,
        bool $withLetterhead = true,
        string $photoLayout = 'balanced',
    ): string
    {
        $photoLayout = in_array($photoLayout, ['balanced', 'large'], true) ? $photoLayout : 'balanced';
        $letterheadHeaderPath = public_path('images/responsiva-membrete-encabezado.jpg');
        $letterheadFooterPath = public_path('images/responsiva-membrete-pie.jpg');
        $letterhead = $withLetterhead
            && is_file($letterheadHeaderPath)
            && is_file($letterheadFooterPath);

        $computer->loadMissing(['employee', 'files']);
        $assignment?->loadMissing('employee');
        $recipientEmployee = $assignment ? $assignment->employee : $computer->employee;
        $employeeName = $assignment?->employee?->full_name
            ?: $assignment?->assigned_to
            ?: $computer->assigned_to
            ?: 'EMPLEADO';
        $recipientPosition = $recipientEmployee?->position ?: 'Puesto no registrado';
        $photos = $computer->files
            ->filter(fn (AssetFile $file) => $file->isImage())
            ->map(fn (AssetFile $file) => $this->photoDataUri($file))
            ->filter()
            ->values();
        $photosPerPage = $photoLayout === 'large' ? 1 : 2;
        $photoPages = $photos->isEmpty() ? collect([collect()]) : $photos->chunk($photosPerPage);
        $letterheadHeaders = [];
        $letterheadFooters = [];

        if ($letterhead) {
            $header = file_get_contents($letterheadHeaderPath);
            $footer = file_get_contents($letterheadFooterPath);

            for ($pageNumber = 1; $pageNumber <= 1 + $photoPages->count(); $pageNumber++) {
                $letterheadHeaders[$pageNumber] = 'data:image/jpeg;base64,'.base64_encode($header."\npage-{$pageNumber}");
                $letterheadFooters[$pageNumber] = 'data:image/jpeg;base64,'.base64_encode($footer."\npage-{$pageNumber}");
            }
        }

        $pdf = Pdf::loadView('pdf.responsiva', [
            'computer' => $computer,
            'employeeName' => $employeeName,
            'recipientPosition' => $recipientPosition,
            'letterhead' => $letterhead,
            'letterheadHeaders' => $letterheadHeaders,
            'letterheadFooters' => $letterheadFooters,
            'photos' => $photos,
            'photoPages' => $photoPages,
            'photoLayout' => $photoLayout,
            'equipmentModel' => $computer->model ?: $computer->name ?: 'EQUIPO DE COMPUTO',
            'formattedValue' => $computer->value !== null
                ? '$'.number_format((float) $computer->value, 2, '.', ',').' MXN'
                : 'VALOR NO REGISTRADO',
            'documentDate' => now()->locale('es')->translatedFormat('j \d\e F \d\e\l Y'),
        ])->setPaper('letter', 'portrait')->output();

        return $letterhead
            ? $this->overlayOddPageLetterhead($pdf, $letterheadHeaderPath, $letterheadFooterPath)
            : $pdf;
    }

    public function specifications(HardwareAsset $computer): array
    {
        $values = [
            'Producto' => $computer->name,
            'Marca' => $computer->brand,
            'Modelo' => $this->different($computer->model, $computer->name) ? $computer->model : null,
            'Serie' => $computer->serial,
            'Folio de inventario' => $computer->code,
            'Procesador' => $computer->processor,
            'RAM' => $computer->ram,
            'Almacenamiento' => $computer->storage,
            'Sistema operativo' => $computer->os,
            'Version del sistema' => $computer->os_version,
            'Arquitectura' => $computer->architecture,
            'BIOS / UEFI' => $computer->bios,
            'Placa base' => $computer->motherboard,
            'Graficos / GPU' => $computer->gpu,
            'Adaptador de red' => $computer->network_adapter,
            'MAC' => $computer->mac_address,
            'Arranque seguro' => $computer->secure_boot,
            'TPM' => $computer->tpm,
            'Valor del equipo' => $computer->value !== null ? '$'.number_format((float) $computer->value, 2, '.', ',') : null,
        ];

        return array_filter($values, fn ($value) => $this->filled($value));
    }

    private function filled(mixed $value): bool
    {
        $value = trim((string) $value);
        return $value !== '' && ! in_array(mb_strtoupper($value), ['-', 'N/A', 'NA', 'NULL', '0', '0000-00-00'], true);
    }

    private function different(mixed $first, mixed $second): bool
    {
        return $this->filled($first) && mb_strtolower(trim((string) $first)) !== mb_strtolower(trim((string) $second));
    }

    private function photoDataUri(AssetFile $file): ?string
    {
        $contents = null;
        if (str_starts_with($file->file_path, 'laravel-local:')) {
            $path = substr($file->file_path, strlen('laravel-local:'));
            if (Storage::disk('local')->exists($path)) $contents = Storage::disk('local')->get($path);
        } else {
            $root = realpath((string) config('inventory.legacy_files_root'));
            if ($root !== false) {
                $path = realpath($root.DIRECTORY_SEPARATOR.ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file->file_path), DIRECTORY_SEPARATOR));
                if ($path !== false && str_starts_with($path, $root.DIRECTORY_SEPARATOR) && is_file($path)) {
                    $contents = file_get_contents($path);
                }
            }
        }

        return $contents === null ? null : 'data:'.$file->previewMimeType().';base64,'.base64_encode($contents);
    }

    private function overlayOddPageLetterhead(string $content, string $headerPath, string $footerPath): string
    {
        $pdf = new Fpdi('P', 'mm', 'Letter');
        $pdf->SetAutoPageBreak(false);
        $pageCount = $pdf->setSourceFile(StreamReader::createByString($content));
        $pages = [];
        $temporaryImages = [];

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $template = $pdf->importPage($pageNumber);
            $pages[$pageNumber] = [
                'template' => $template,
                'size' => $pdf->getTemplateSize($template),
            ];
        }

        try {
            foreach ($pages as $pageNumber => $page) {
                $size = $page['size'];
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($page['template'], 0, 0, $size['width'], $size['height'], true);

                if ($pageNumber % 2 === 1) {
                    $pageHeader = tempnam(sys_get_temp_dir(), 'responsiva-header-');
                    $pageFooter = tempnam(sys_get_temp_dir(), 'responsiva-footer-');
                    file_put_contents($pageHeader, file_get_contents($headerPath));
                    file_put_contents($pageFooter, file_get_contents($footerPath));
                    $temporaryImages[] = $pageHeader;
                    $temporaryImages[] = $pageFooter;
                    $pdf->Image($pageHeader, 0, 0, $size['width'], 38.17, 'JPG');
                    $pdf->Image($pageFooter, 0, 243.9, $size['width'], 35.5, 'JPG');
                }
            }

            return $pdf->Output('S');
        } finally {
            foreach ($temporaryImages as $temporaryImage) {
                @unlink($temporaryImage);
            }
        }
    }

}
