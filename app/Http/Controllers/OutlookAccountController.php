<?php

namespace App\Http\Controllers;

use App\Http\Requests\OutlookAccountRequest;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\OutlookAccount;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OutlookAccountController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', OutlookAccount::class);
        return view('credentials.outlook.create', ['outlookAccount' => new OutlookAccount([
            'estatus' => 'ACTIVA',
            'servidor_entrada' => 'mail.totalground.com',
            'puerto_entrada' => '995',
            'ssl_entrada' => true,
            'servidor_salida' => 'mail.totalground.com',
            'puerto_salida' => '465',
            'cifrado_salida' => 'SSL/TLS',
        ]), 'employees' => $this->employees()]);
    }

    public function store(OutlookAccountRequest $request, AuditLogger $audit): RedirectResponse
    {
        $account = DB::transaction(function () use ($request, $audit) {
            $account = OutlookAccount::create($request->validated());
            $audit->record('create', 'correos_outlook', $account->id, null, $account->getAttributes());
            return $account;
        });
        return redirect()->route('outlook-accounts.show', $account)->with('success', 'Correo Outlook registrado.');
    }

    public function show(OutlookAccount $outlookAccount): View
    {
        $this->authorize('view', $outlookAccount);
        $auditLogs = request()->user()->can('viewAudit', $outlookAccount)
            ? AuditLog::where('entity', 'correos_outlook')->where('entity_id', (string) $outlookAccount->id)->latest()->limit(20)->get()
            : collect();
        $outlookAccount->load('employee');
        return view('credentials.outlook.show', compact('outlookAccount', 'auditLogs'));
    }

    public function edit(OutlookAccount $outlookAccount): View
    {
        $this->authorize('update', $outlookAccount);
        return view('credentials.outlook.edit', ['outlookAccount' => $outlookAccount, 'employees' => $this->employees()]);
    }

    public function update(OutlookAccountRequest $request, OutlookAccount $outlookAccount, AuditLogger $audit): RedirectResponse
    {
        $before = $outlookAccount->getAttributes();
        $data = $request->validated();
        if (blank($data['contraseña'] ?? null)) unset($data['contraseña']);
        DB::transaction(function () use ($outlookAccount, $data, $before, $audit) {
            $outlookAccount->update($data);
            $audit->record('update', 'correos_outlook', $outlookAccount->id, $before, $outlookAccount->fresh()->getAttributes());
        });
        return redirect()->route('outlook-accounts.show', $outlookAccount)->with('success', 'Correo Outlook actualizado.');
    }

    public function destroy(OutlookAccount $outlookAccount, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $outlookAccount);
        $before = $outlookAccount->getAttributes();
        DB::transaction(function () use ($outlookAccount, $before, $audit) {
            $outlookAccount->delete();
            $audit->record('delete', 'correos_outlook', $outlookAccount->id, $before, null);
        });
        return redirect()->route('credentials.index', ['type' => 'outlook'])->with('success', 'Correo Outlook eliminado.');
    }

    private function employees()
    {
        return Employee::where('status', 'Activo')->orderBy('full_name')->get(['id', 'full_name', 'department']);
    }
}
