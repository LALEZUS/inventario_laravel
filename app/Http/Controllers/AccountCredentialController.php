<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountCredentialRequest;
use App\Models\AccountCredential;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountCredentialController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', AccountCredential::class);
        return view('credentials.accounts.create', ['accountCredential' => new AccountCredential, 'employees' => $this->employees()]);
    }

    public function store(AccountCredentialRequest $request, AuditLogger $audit): RedirectResponse
    {
        $credential = DB::transaction(function () use ($request, $audit) {
            $data = $this->normalized($request->validated());
            $credential = AccountCredential::create($data);
            $audit->record('create', 'account_management', $credential->id, null, $credential->getAttributes());
            return $credential;
        });
        return redirect()->route('account-credentials.show', $credential)->with('success', 'Cuenta registrada.');
    }

    public function show(AccountCredential $accountCredential): View
    {
        $this->authorize('view', $accountCredential);
        $accountCredential->load('employee');
        $auditLogs = request()->user()->can('viewAudit', $accountCredential)
            ? AuditLog::where('entity', 'account_management')->where('entity_id', (string) $accountCredential->id)->latest()->limit(20)->get()
            : collect();
        return view('credentials.accounts.show', compact('accountCredential', 'auditLogs'));
    }

    public function edit(AccountCredential $accountCredential): View
    {
        $this->authorize('update', $accountCredential);
        return view('credentials.accounts.edit', ['accountCredential' => $accountCredential, 'employees' => $this->employees()]);
    }

    public function update(AccountCredentialRequest $request, AccountCredential $accountCredential, AuditLogger $audit): RedirectResponse
    {
        $before = $accountCredential->getAttributes();
        $data = $this->normalized($request->validated());
        if (blank($data['password'] ?? null)) unset($data['password']);
        DB::transaction(function () use ($accountCredential, $data, $before, $audit) {
            $accountCredential->update($data);
            $audit->record('update', 'account_management', $accountCredential->id, $before, $accountCredential->fresh()->getAttributes());
        });
        return redirect()->route('account-credentials.show', $accountCredential)->with('success', 'Cuenta actualizada.');
    }

    public function destroy(AccountCredential $accountCredential, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $accountCredential);
        $before = $accountCredential->getAttributes();
        DB::transaction(function () use ($accountCredential, $before, $audit) {
            $accountCredential->delete();
            $audit->record('delete', 'account_management', $accountCredential->id, $before, null);
        });
        return redirect()->route('credentials.index', ['type' => 'accounts'])->with('success', 'Cuenta eliminada.');
    }

    private function normalized(array $data): array
    {
        if (! empty($data['employee_id']) && blank($data['assigned_to'] ?? null)) {
            $data['assigned_to'] = Employee::find($data['employee_id'])?->full_name;
        }
        return $data;
    }

    private function employees()
    {
        return Employee::orderBy('full_name')->get(['id', 'full_name', 'status']);
    }
}
