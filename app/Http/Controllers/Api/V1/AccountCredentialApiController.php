<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccountCredentialApiRequest;
use App\Http\Resources\AccountCredentialResource;
use App\Models\AccountCredential;
use App\Models\Employee;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountCredentialApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AccountCredential::class);

        $query = AccountCredential::with('employee');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('account_type', 'like', "%{$search}%")
                  ->orWhere('assigned_to', 'like', "%{$search}%")
                  ->orWhere('comments', 'like', "%{$search}%")
                  ->orWhereHas('employee', fn ($emp) => $emp->where('full_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $items = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->latest('updated_at')
        )->paginate(20);

        return response()->json([
            'success' => true,
            'data' => AccountCredentialResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(AccountCredential $accountCredential): JsonResponse
    {
        $this->authorize('view', $accountCredential);
        $accountCredential->load('employee');

        return response()->json([
            'success' => true,
            'data' => new AccountCredentialResource($accountCredential, true),
        ]);
    }

    public function store(AccountCredentialApiRequest $request, AuditLogger $logger): JsonResponse
    {
        $this->authorize('create', AccountCredential::class);

        $data = $this->normalized($request->validated());

        $credential = DB::transaction(function () use ($data, $logger) {
            $m = AccountCredential::create($data);
            $logger->record('create', 'account_management', $m->id, null, $m->getAttributes());
            return $m;
        });

        return response()->json([
            'success' => true,
            'message' => 'Cuenta corporativa registrada correctamente.',
            'data' => new AccountCredentialResource($credential->fresh()->load('employee'), true),
        ], 201);
    }

    public function update(AccountCredentialApiRequest $request, AccountCredential $accountCredential, AuditLogger $logger): JsonResponse
    {
        $this->authorize('update', $accountCredential);

        $before = $accountCredential->getAttributes();
        $data = $this->normalized($request->validated());

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        DB::transaction(function () use ($accountCredential, $data, $before, $logger) {
            $accountCredential->update($data);
            $logger->record('update', 'account_management', $accountCredential->id, $before, $accountCredential->fresh()->getAttributes());
        });

        return response()->json([
            'success' => true,
            'message' => 'Cuenta corporativa actualizada correctamente.',
            'data' => new AccountCredentialResource($accountCredential->fresh()->load('employee'), true),
        ]);
    }

    public function destroy(AccountCredential $accountCredential, AuditLogger $logger): JsonResponse
    {
        $this->authorize('delete', $accountCredential);

        $before = $accountCredential->getAttributes();
        $accountCredential->delete();

        $logger->record('delete', 'account_management', $accountCredential->id, $before, null);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta corporativa eliminada correctamente.',
        ]);
    }

    public function secret(AccountCredential $accountCredential): JsonResponse
    {
        $this->authorize('viewSensitive', $accountCredential);

        return response()->json([
            'success' => true,
            'data' => [
                'password' => $accountCredential->password,
            ],
        ])->header('Cache-Control', 'no-store, private');
    }

    private function normalized(array $data): array
    {
        if (! empty($data['employee_id']) && blank($data['assigned_to'] ?? null)) {
            $data['assigned_to'] = Employee::find($data['employee_id'])?->full_name;
        }
        return $data;
    }
}
