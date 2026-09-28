<?php

namespace App\Http\Controllers;

use App\Http\Requests\TonerRequest;
use App\Models\AuditLog;
use App\Models\Printer;
use App\Models\Toner;
use App\Services\TonerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TonerController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', Toner::class);

        return view('supplies.toner.create', ['toner' => new Toner(['status' => 'NUEVO', 'quantity' => 0])]);
    }

    public function store(TonerRequest $request, TonerService $service): RedirectResponse
    {
        $toner = $service->store($request->validated());

        return redirect()->route('toner.show', $toner)->with('success', 'Toner registrado correctamente.');
    }

    public function show(Toner $toner): View
    {
        $this->authorize('view', $toner);
        $printers = Printer::all()->filter(fn (Printer $printer) => in_array($toner->id, $printer->linkedTonerIds(), true))->values();
        $auditLogs = request()->user()->can('viewAudit', $toner)
            ? AuditLog::where('entity', 'toner')->where('entity_id', (string) $toner->id)->latest('created_at')->limit(20)->get()
            : collect();

        return view('supplies.toner.show', compact('toner', 'printers', 'auditLogs'));
    }

    public function edit(Toner $toner): View
    {
        $this->authorize('update', $toner);

        return view('supplies.toner.edit', compact('toner'));
    }

    public function update(TonerRequest $request, Toner $toner, TonerService $service): RedirectResponse
    {
        $service->update($toner, $request->validated());

        return redirect()->route('toner.show', $toner)->with('success', 'Toner actualizado correctamente.');
    }

    public function updateQuantity(Request $request, Toner $toner, TonerService $service): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $toner);
        $quantity = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
        ])['quantity'];

        $service->update($toner, ['quantity' => $quantity]);

        if ($request->expectsJson()) {
            return response()->json(['quantity' => (int) $quantity]);
        }

        return redirect()->to(route('supplies.index', ['type' => 'toner']) . '#toner-' . $toner->id)
            ->with('success', 'Existencia actualizada correctamente.');
    }

    public function destroy(Toner $toner, TonerService $service): RedirectResponse
    {
        $this->authorize('delete', $toner);
        $service->delete($toner);

        return redirect()->route('supplies.index', ['type' => 'toner'])->with('success', 'Toner eliminado correctamente.');
    }
}
