<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnterpriseNetworkRequest;
use App\Models\AuditLog;
use App\Models\EnterpriseNetwork;
use App\Services\EnterpriseNetworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EnterpriseNetworkController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', EnterpriseNetwork::class);

        return view('network.enterprise.create', ['enterpriseNetwork' => new EnterpriseNetwork]);
    }

    public function store(EnterpriseNetworkRequest $request, EnterpriseNetworkService $service): RedirectResponse
    {
        $network = $service->store($request->validated());

        return redirect()->route('enterprise-networks.show', $network)->with('success', 'Red empresarial registrada.');
    }

    public function show(EnterpriseNetwork $enterpriseNetwork): View
    {
        $this->authorize('view', $enterpriseNetwork);
        $auditLogs = request()->user()->can('viewAudit', $enterpriseNetwork)
            ? AuditLog::where('entity', 'enterprise_networks')->where('entity_id', (string) $enterpriseNetwork->id)->latest('created_at')->limit(20)->get()
            : collect();

        return view('network.enterprise.show', compact('enterpriseNetwork', 'auditLogs'));
    }

    public function edit(EnterpriseNetwork $enterpriseNetwork): View
    {
        $this->authorize('update', $enterpriseNetwork);

        return view('network.enterprise.edit', compact('enterpriseNetwork'));
    }

    public function update(EnterpriseNetworkRequest $request, EnterpriseNetwork $enterpriseNetwork, EnterpriseNetworkService $service): RedirectResponse
    {
        $service->update($enterpriseNetwork, $request->validated());

        return redirect()->route('enterprise-networks.show', $enterpriseNetwork)->with('success', 'Red empresarial actualizada.');
    }

    public function destroy(EnterpriseNetwork $enterpriseNetwork, EnterpriseNetworkService $service): RedirectResponse
    {
        $this->authorize('delete', $enterpriseNetwork);
        $service->delete($enterpriseNetwork);

        return redirect()->route('network.index', ['type' => 'networks'])->with('success', 'Red empresarial eliminada.');
    }
}