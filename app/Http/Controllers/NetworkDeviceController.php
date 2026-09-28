<?php

namespace App\Http\Controllers;

use App\Http\Requests\NetworkDeviceRequest;
use App\Models\AuditLog;
use App\Models\NetworkDevice;
use App\Services\NetworkDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NetworkDeviceController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', NetworkDevice::class);

        return view('network.devices.create', ['networkDevice' => new NetworkDevice(['status' => 'Activo'])]);
    }

    public function store(NetworkDeviceRequest $request, NetworkDeviceService $service): RedirectResponse
    {
        $device = $service->store($request->validated());

        return redirect()->route('network-devices.show', $device)->with('success', 'Dispositivo de red registrado.');
    }

    public function show(NetworkDevice $networkDevice): View
    {
        $this->authorize('view', $networkDevice);
        $auditLogs = request()->user()->can('viewAudit', $networkDevice)
            ? AuditLog::where('entity', 'network_devices')->where('entity_id', (string) $networkDevice->id)->latest('created_at')->limit(20)->get()
            : collect();

        return view('network.devices.show', compact('networkDevice', 'auditLogs'));
    }

    public function edit(NetworkDevice $networkDevice): View
    {
        $this->authorize('update', $networkDevice);

        return view('network.devices.edit', compact('networkDevice'));
    }

    public function update(NetworkDeviceRequest $request, NetworkDevice $networkDevice, NetworkDeviceService $service): RedirectResponse
    {
        $service->update($networkDevice, $request->validated());

        return redirect()->route('network-devices.show', $networkDevice)->with('success', 'Dispositivo de red actualizado.');
    }

    public function destroy(NetworkDevice $networkDevice, NetworkDeviceService $service): RedirectResponse
    {
        $this->authorize('delete', $networkDevice);
        $service->delete($networkDevice);

        return redirect()->route('network.index', ['type' => 'devices'])->with('success', 'Dispositivo eliminado.');
    }
}