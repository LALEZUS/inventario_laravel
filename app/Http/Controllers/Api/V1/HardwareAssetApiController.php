<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\HardwareAssetRequest;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\HardwareAssetResource;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Services\AssetAssignmentService;
use App\Services\AuditLogger;
use App\Services\HardwareAssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HardwareAssetApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', HardwareAsset::class);

        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));
        $brand = trim((string) $request->query('brand'));
        $zone = trim((string) $request->query('zone'));

        $query = HardwareAsset::with('employee')
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('brand', 'like', $term)
                        ->orWhere('model', 'like', $term)
                        ->orWhere('serial', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('assigned_user', 'like', $term)
                        ->orWhere('processor', 'like', $term)
                        ->orWhereHas('employee', fn ($e) => $e->where('full_name', 'like', $term));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($brand !== '', fn ($q) => $q->where('brand', $brand))
            ->when($zone !== '', fn ($q) => $q->where('zone', $zone));

        $computers = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->orderBy('name')
        )->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Computadoras obtenidas correctamente.',
            'data' => HardwareAssetResource::collection($computers),
            'meta' => [
                'pagination' => [
                    'total' => $computers->total(),
                    'per_page' => $computers->perPage(),
                    'current_page' => $computers->currentPage(),
                    'last_page' => $computers->lastPage(),
                ],
            ],
        ], 200);
    }

    public function store(HardwareAssetRequest $request, HardwareAssetService $service): JsonResponse
    {
        $this->authorize('create', HardwareAsset::class);

        $computer = $service->store($request->validated(), $request->file('nfo_file'), $request->user());
        $computer->load('employee');

        return response()->json([
            'success' => true,
            'message' => 'Computadora registrada correctamente.',
            'data' => new HardwareAssetResource($computer),
        ], 201);
    }

    public function show(HardwareAsset $computer): JsonResponse
    {
        $this->authorize('view', $computer);

        $computer->load(['employee', 'peripherals', 'assignments.employee', 'maintenanceLogs']);

        return response()->json([
            'success' => true,
            'message' => 'Computadora obtenida correctamente.',
            'data' => new HardwareAssetResource($computer),
        ], 200);
    }

    public function update(
        HardwareAssetRequest $request,
        HardwareAsset $computer,
        HardwareAssetService $service
    ): JsonResponse {
        $this->authorize('update', $computer);

        $updated = $service->update($computer->id, $request->validated(), $request->file('nfo_file'), $request->user());
        $updated->load('employee');

        return response()->json([
            'success' => true,
            'message' => 'Computadora actualizada correctamente.',
            'data' => new HardwareAssetResource($updated),
        ], 200);
    }

    public function destroy(HardwareAsset $computer, AuditLogger $audit): JsonResponse
    {
        $this->authorize('delete', $computer);

        $before = $computer->getAttributes();
        DB::transaction(function () use ($computer, $before, $audit) {
            $computer->delete();
            $audit->record('delete', 'hardware_assets', $computer->id, $before, null, request()->user());
        });

        return response()->json([
            'success' => true,
            'message' => 'Computadora eliminada correctamente.',
        ], 200);
    }

    public function assign(Request $request, HardwareAsset $computer, AssetAssignmentService $service): JsonResponse
    {
        $this->authorize('update', $computer);

        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date_assigned' => ['required', 'date'],
            'condition_on_assign' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        $assignment = $service->assignComputer($computer->id, $data, $employee, $request->user());
        $assignment->load('employee');

        return response()->json([
            'success' => true,
            'message' => 'Asignación registrada correctamente.',
            'data' => new AssignmentResource($assignment),
        ], 200);
    }

    public function returnAsset(
        Request $request,
        HardwareAsset $computer,
        Assignment $assignment,
        AssetAssignmentService $service
    ): JsonResponse {
        $this->authorize('update', $computer);

        $data = $request->validate([
            'date_returned' => ['required', 'date', 'after_or_equal:'.$assignment->date_assigned->format('Y-m-d')],
            'condition_on_return' => ['nullable', 'string', 'max:50'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $updatedAssignment = $service->returnComputer($computer->id, $assignment->id, $data, $request->user());
        $updatedAssignment->load('employee');

        return response()->json([
            'success' => true,
            'message' => 'Devolución registrada correctamente.',
            'data' => new AssignmentResource($updatedAssignment),
        ], 200);
    }
}
