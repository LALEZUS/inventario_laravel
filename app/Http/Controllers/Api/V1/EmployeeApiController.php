<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));

        $query = Employee::when($search !== '', function ($query) use ($search) {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('full_name', 'like', $term)
                    ->orWhere('department', 'like', $term)
                    ->orWhere('position', 'like', $term)
                    ->orWhere('email_corporate', 'like', $term)
                    ->orWhere('extension', 'like', $term);
            });
        })
        ->when($status !== '', fn ($q) => $q->where('status', $status));

        $employees = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->orderBy('full_name')
        )->paginate($request->integer('per_page', 20))
        ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Empleados obtenidos correctamente.',
            'data' => EmployeeResource::collection($employees),
            'meta' => [
                'pagination' => [
                    'total' => $employees->total(),
                    'per_page' => $employees->perPage(),
                    'current_page' => $employees->currentPage(),
                    'last_page' => $employees->lastPage(),
                ],
            ],
        ], 200);
    }

    public function store(EmployeeRequest $request, AuditLogger $audit): JsonResponse
    {
        $this->authorize('create', Employee::class);

        $employee = DB::transaction(function () use ($request, $audit) {
            $employee = Employee::create($request->validated());
            $audit->record('create', 'employees', $employee->id, null, $employee->getAttributes(), $request->user());

            return $employee;
        });

        return response()->json([
            'success' => true,
            'message' => 'Empleado registrado correctamente.',
            'data' => new EmployeeResource($employee),
        ], 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        $this->authorize('view', $employee);

        $employee->load([
            'hardwareAssets' => fn ($query) => $query->orderBy('name'),
            'cellphones' => fn ($query) => $query->orderBy('model'),
            'peripherals' => fn ($query) => $query->orderBy('name'),
            'printers' => fn ($query) => $query->orderBy('name'),
            'outlookAccounts' => fn ($query) => $query->orderBy('correo'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Empleado obtenido correctamente.',
            'data' => new EmployeeResource($employee),
        ], 200);
    }

    public function update(EmployeeRequest $request, Employee $employee, AuditLogger $audit): JsonResponse
    {
        $this->authorize('update', $employee);

        $before = $employee->getAttributes();
        DB::transaction(function () use ($request, $employee, $before, $audit) {
            $employee->update($request->validated());
            $audit->record('update', 'employees', $employee->id, $before, $employee->fresh()->getAttributes(), $request->user());
        });

        return response()->json([
            'success' => true,
            'message' => 'Empleado actualizado correctamente.',
            'data' => new EmployeeResource($employee->fresh()),
        ], 200);
    }

    public function destroy(Employee $employee, AuditLogger $audit): JsonResponse
    {
        $this->authorize('delete', $employee);

        $before = $employee->getAttributes();
        DB::transaction(function () use ($employee, $before, $audit) {
            $employee->delete();
            $audit->record('delete', 'employees', $employee->id, $before, null, request()->user());
        });

        return response()->json([
            'success' => true,
            'message' => 'Empleado eliminado correctamente.',
        ], 200);
    }
}
