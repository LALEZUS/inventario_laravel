<?php

namespace App\Http\Controllers;

use App\Models\HardwareAsset;
use App\Models\MaintenanceLog;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenanceLogController extends Controller
{
    public function store(Request $request, HardwareAsset $computer, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $computer);
        $data = $this->validated($request);
        $log = $computer->maintenanceLogs()->create($data + ['asset_type' => 'inventory']);
        $audit->record('create', 'maintenance_logs', $log->id, null, $log->getAttributes());
        return back()->with('success', 'Mantenimiento agregado a la bitacora.');
    }

    public function update(Request $request, HardwareAsset $computer, MaintenanceLog $maintenance, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $computer);
        $this->belongsTo($computer, $maintenance);
        $before = $maintenance->getAttributes();
        $maintenance->update($this->validated($request));
        $audit->record('update', 'maintenance_logs', $maintenance->id, $before, $maintenance->fresh()->getAttributes());
        return back()->with('success', 'Mantenimiento actualizado.');
    }

    public function destroy(HardwareAsset $computer, MaintenanceLog $maintenance, AuditLogger $audit): RedirectResponse
    {
        abort_unless(request()->user()->role === 'admin', 403);
        $this->belongsTo($computer, $maintenance);
        $before = $maintenance->getAttributes();
        $maintenance->delete();
        $audit->record('delete', 'maintenance_logs', $maintenance->id, $before, null);
        return back()->with('success', 'Mantenimiento eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date'], 'category' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:2000'], 'diagnosis' => ['nullable', 'string', 'max:2000'],
            'technician' => ['nullable', 'string', 'max:100'], 'provider' => ['nullable', 'string', 'max:120'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'], 'next_date' => ['nullable', 'date', 'after_or_equal:date'],
            'status' => ['required', 'in:Programado,En proceso,Completado,Cancelado'],
        ]);
    }

    private function belongsTo(HardwareAsset $computer, MaintenanceLog $maintenance): void
    {
        abort_unless($maintenance->asset_type === 'inventory' && $maintenance->asset_id === $computer->id, 404);
    }
}
