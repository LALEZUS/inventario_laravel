<?php

namespace App\Http\Controllers;

use App\Http\Requests\SoftwareLicenseRequest;
use App\Models\AuditLog;
use App\Models\SoftwareLicense;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SoftwareLicenseController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', SoftwareLicense::class);
        return view('credentials.licenses.create', ['softwareLicense' => new SoftwareLicense]);
    }

    public function store(SoftwareLicenseRequest $request, AuditLogger $audit): RedirectResponse
    {
        $license = DB::transaction(function () use ($request, $audit) {
            $license = SoftwareLicense::create($request->validated());
            $audit->record('create', 'licenses', $license->id, null, $license->getAttributes());
            return $license;
        });
        return redirect()->route('software-licenses.show', $license)->with('success', 'Licencia registrada.');
    }

    public function show(SoftwareLicense $softwareLicense): View
    {
        $this->authorize('view', $softwareLicense);
        $auditLogs = request()->user()->can('viewAudit', $softwareLicense)
            ? AuditLog::where('entity', 'licenses')->where('entity_id', (string) $softwareLicense->id)->latest()->limit(20)->get()
            : collect();
        return view('credentials.licenses.show', compact('softwareLicense', 'auditLogs'));
    }

    public function edit(SoftwareLicense $softwareLicense): View
    {
        $this->authorize('update', $softwareLicense);
        return view('credentials.licenses.edit', compact('softwareLicense'));
    }

    public function update(SoftwareLicenseRequest $request, SoftwareLicense $softwareLicense, AuditLogger $audit): RedirectResponse
    {
        $before = $softwareLicense->getAttributes();
        $data = $request->validated();
        foreach (['key_value', 'password'] as $secret) {
            if (blank($data[$secret] ?? null)) unset($data[$secret]);
        }
        DB::transaction(function () use ($softwareLicense, $data, $before, $audit) {
            $softwareLicense->update($data);
            $audit->record('update', 'licenses', $softwareLicense->id, $before, $softwareLicense->fresh()->getAttributes());
        });
        return redirect()->route('software-licenses.show', $softwareLicense)->with('success', 'Licencia actualizada.');
    }

    public function destroy(SoftwareLicense $softwareLicense, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $softwareLicense);
        $before = $softwareLicense->getAttributes();
        DB::transaction(function () use ($softwareLicense, $before, $audit) {
            $softwareLicense->delete();
            $audit->record('delete', 'licenses', $softwareLicense->id, $before, null);
        });
        return redirect()->route('credentials.index', ['type' => 'licenses'])->with('success', 'Licencia eliminada.');
    }
}
