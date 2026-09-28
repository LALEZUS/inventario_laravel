<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AssetFile;
use App\Models\AuditLog;
use App\Models\Assignment;
use App\Models\HardwareAsset;
use App\Services\AuditLogger;
use App\Services\ResponsivaGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class HardwareAssetDetailApiController extends Controller
{
    public function credentials(Request $request, HardwareAsset $computer): JsonResponse
    {
        $this->authorize('viewSensitive', $computer);

        return response()->json([
            'success' => true,
            'message' => 'Credenciales obtenidas correctamente.',
            'data' => [
                'admin_password' => $computer->admin_password,
                'anydesk_id' => $computer->anydesk_id,
                'rustdesk_id' => $computer->rustdesk_id,
            ],
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function photos(Request $request, HardwareAsset $computer): JsonResponse
    {
        $this->authorize('view', $computer);

        $photos = $computer->files()
            ->latest('uploaded_at')
            ->get()
            ->filter(fn (AssetFile $file) => $file->isImage())
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Fotos obtenidas correctamente.',
            'data' => $photos->map(fn ($p) => [
                'id' => $p->id,
                'label' => $p->label,
                'original_name' => $p->original_name,
                'file_type' => $p->file_type,
                'file_size' => $p->file_size,
                'uploaded_at' => $p->uploaded_at?->toIso8601String(),
                'preview_url' => route('api.v1.files.preview', $p),
                'download_url' => route('api.v1.files.download', $p),
            ]),
        ]);
    }

    public function uploadPhoto(Request $request, HardwareAsset $computer, AuditLogger $audit): JsonResponse
    {
        $this->authorize('upload', $computer);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:10240'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $validated['photo'];
        $path = $file->store('assets/inventory/'.$computer->id, 'local');

        $assetFile = AssetFile::create([
            'asset_type' => 'inventory',
            'asset_id' => $computer->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => 'laravel-local:'.$path,
            'file_type' => $file->getClientMimeType() ?: 'image/jpeg',
            'file_size' => $file->getSize(),
            'label' => $validated['label'] ?? 'Foto de evidencia',
            'comments' => 'Subida desde Flutter API',
        ]);

        $audit->record('upload', 'hardware_assets', $computer->id, null, [
            'asset_file_id' => $assetFile->id,
            'filename' => $file->getClientOriginalName(),
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Foto subida correctamente.',
            'data' => [
                'id' => $assetFile->id,
                'label' => $assetFile->label,
                'original_name' => $assetFile->original_name,
                'file_type' => $assetFile->file_type,
                'file_size' => $assetFile->file_size,
                'uploaded_at' => $assetFile->uploaded_at?->toIso8601String(),
                'preview_url' => route('api.v1.files.preview', $assetFile),
                'download_url' => route('api.v1.files.download', $assetFile),
            ],
        ], 201);
    }

    public function files(Request $request, HardwareAsset $computer): JsonResponse
    {
        $this->authorize('view', $computer);

        $files = $computer->files()
            ->latest('uploaded_at')
            ->get()
            ->reject(fn (AssetFile $file) => $file->isImage())
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Archivos adjuntos obtenidos correctamente.',
            'data' => $files->map(fn ($f) => [
                'id' => $f->id,
                'label' => $f->label,
                'original_name' => $f->original_name,
                'file_type' => $f->file_type,
                'file_size' => $f->file_size,
                'uploaded_at' => $f->uploaded_at?->toIso8601String(),
                'download_url' => route('api.v1.files.download', $f),
                'preview_url' => $f->isPreviewable()
                    ? route('api.v1.files.preview', $f)
                    : null,
            ]),
        ]);
    }

    public function assignments(Request $request, HardwareAsset $computer): JsonResponse
    {
        $this->authorize('view', $computer);

        $assignments = $computer->assignments()
            ->with('employee')
            ->latest('date_assigned')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Historial de asignaciones obtenido correctamente.',
            'data' => $assignments->map(fn ($a) => [
                'id' => $a->id,
                'asset_type' => $a->asset_type,
                'asset_id' => $a->asset_id,
                'employee_id' => $a->employee_id,
                'assigned_to' => $a->assigned_to ?: $a->employee?->full_name,
                'department' => $a->department ?: $a->employee?->department,
                'date_assigned' => $a->date_assigned?->format('Y-m-d') ?: (string) $a->date_assigned,
                'date_returned' => $a->date_returned?->format('Y-m-d') ?: ($a->date_returned ? (string) $a->date_returned : null),
                'condition_on_assign' => $a->condition_on_assign,
                'condition_on_return' => $a->condition_on_return,
                'notes' => $a->notes,
                'comments' => $a->comments,
                'is_active' => $a->date_returned === null,
                'employee' => $a->employee ? [
                    'id' => $a->employee->id,
                    'full_name' => $a->employee->full_name,
                    'department' => $a->employee->department,
                    'position' => $a->employee->position,
                ] : null,
            ]),
        ]);
    }

    public function maintenanceLogs(Request $request, HardwareAsset $computer): JsonResponse
    {
        $this->authorize('view', $computer);

        $logs = $computer->maintenanceLogs()
            ->latest('date')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Historial de mantenimientos obtenido correctamente.',
            'data' => $logs->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->category ?? $m->status ?? 'Mantenimiento',
                'description' => $m->description ?? '',
                'date' => $m->date?->format('Y-m-d') ?: ($m->date ? (string) $m->date : null),
                'performed_by' => $m->technician ?: $m->provider ?: 'Soporte',
                'next_date' => $m->next_date?->format('Y-m-d') ?: ($m->next_date ? (string) $m->next_date : null),
                'cost' => $m->cost,
                'comments' => $m->diagnosis,
            ]),
        ]);
    }

    public function auditLogs(Request $request, HardwareAsset $computer): JsonResponse
    {
        $this->authorize('viewAudit', $computer);

        $assignmentIds = $computer->assignments()->pluck('id')->all();
        $fileIds = $computer->files()->pluck('id')->all();
        $maintIds = $computer->maintenanceLogs()->pluck('id')->all();

        $logs = AuditLog::query()
            ->where(function ($q) use ($computer, $assignmentIds, $fileIds, $maintIds) {
                $q->where(fn ($sub) => $sub->where('entity', 'hardware_assets')->where('entity_id', (string) $computer->id))
                    ->orWhere(fn ($sub) => $sub->where('entity', 'assignments')->whereIn('entity_id', array_map('strval', $assignmentIds)))
                    ->orWhere(fn ($sub) => $sub->where('entity', 'asset_files')->whereIn('entity_id', array_map('strval', $fileIds)))
                    ->orWhere(fn ($sub) => $sub->where('entity', 'maintenance_logs')->whereIn('entity_id', array_map('strval', $maintIds)));
            })
            ->latest('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Bitácora de auditoría obtenida correctamente.',
            'data' => $logs->map(function ($log) {
                $actionLabel = match (strtolower((string) $log->action)) {
                    'create', 'crear' => 'Creación de Registro',
                    'update', 'actualizar', 'editar' => 'Actualización de Datos',
                    'delete', 'eliminar' => 'Eliminación',
                    'assign', 'asignar' => 'Asignación de Equipo',
                    'return', 'devolver' => 'Devolución de Equipo',
                    'upload' => 'Archivo/Foto Adjuntada',
                    'generate_responsiva' => 'Generación de Responsiva PDF',
                    default => ucfirst($log->action),
                };

                $entityLabel = match (strtolower((string) $log->entity)) {
                    'hardware_assets' => 'Computadora',
                    'assignments' => 'Asignación',
                    'asset_files' => 'Archivo/Foto',
                    'maintenance_logs' => 'Mantenimiento',
                    default => $log->entity,
                };

                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'action_label' => $actionLabel,
                    'entity' => $log->entity,
                    'entity_label' => $entityLabel,
                    'description' => "Acción '{$actionLabel}' realizada sobre {$entityLabel}",
                    'user_name' => $log->user_name ?: 'Sistema',
                    'role' => $log->role ?: 'sistema',
                    'created_at' => $log->created_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    public function downloadResponsiva(
        Request $request,
        HardwareAsset $computer,
        ResponsivaGenerator $generator,
        AuditLogger $audit,
    ): Response {
        $this->authorize('upload', $computer);

        $assignment = $this->responsivaAssignment($request, $computer);
        $withLetterhead = $request->input('letterhead', '1') !== '0';
        $photoLayout = $this->responsivaPhotoLayout($request);
        $computer->load(['employee', 'files']);
        $recipient = $assignment?->employee?->full_name
            ?: $assignment?->assigned_to
            ?: $computer->assigned_to
            ?: 'empleado';
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
                'comments' => 'Generada desde Flutter con el formato de carta responsiva',
            ]);
            $audit->record('generate_responsiva', 'hardware_assets', $computer->id, null, [
                'asset_file_id' => $file->id,
                'filename' => $filename,
                'letterhead' => $withLetterhead,
                'photo_layout' => $photoLayout,
            ], $request->user());
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

    public function previewResponsiva(
        Request $request,
        HardwareAsset $computer,
        ResponsivaGenerator $generator,
    ): Response {
        $this->authorize('upload', $computer);

        $assignment = $this->responsivaAssignment($request, $computer);
        $withLetterhead = $request->input('letterhead', '1') !== '0';
        $photoLayout = $this->responsivaPhotoLayout($request);
        $computer->load(['employee', 'files']);
        $pdf = $generator->generate($computer, $assignment, $withLetterhead, $photoLayout);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Vista_previa_responsiva.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function responsivaAssignment(Request $request, HardwareAsset $computer): ?Assignment
    {
        if (! $request->filled('assignment_id')) {
            return null;
        }

        return Assignment::where('asset_type', 'inventory')
            ->where('asset_id', $computer->id)
            ->with('employee')
            ->findOrFail($request->integer('assignment_id'));
    }

    private function responsivaPhotoLayout(Request $request): string
    {
        $layout = (string) $request->input('photo_layout', 'balanced');

        return in_array($layout, ['balanced', 'large'], true) ? $layout : 'balanced';
    }
}
