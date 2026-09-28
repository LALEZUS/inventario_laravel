<?php

namespace App\Http\Controllers;

use App\Http\Requests\InkRequest;
use App\Models\AuditLog;
use App\Models\Ink;
use App\Models\Printer;
use App\Services\InkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InkController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', Ink::class);

        return view('supplies.inks.create', ['ink' => new Ink(['status' => 'Disponible', 'quantity' => 0])]);
    }

    public function store(InkRequest $request, InkService $service): RedirectResponse
    {
        $ink = $service->store($request->validated());

        return redirect()->route('inks.show', $ink)->with('success', 'Tinta registrada correctamente.');
    }

    public function show(Ink $ink): View
    {
        $this->authorize('view', $ink);
        $printers = Printer::all()->filter(fn (Printer $printer) => in_array($ink->id, $printer->linkedInkIds(), true))->values();
        $auditLogs = request()->user()->can('viewAudit', $ink)
            ? AuditLog::where('entity', 'inks')->where('entity_id', (string) $ink->id)->latest('created_at')->limit(20)->get()
            : collect();

        return view('supplies.inks.show', compact('ink', 'printers', 'auditLogs'));
    }

    public function edit(Ink $ink): View
    {
        $this->authorize('update', $ink);

        return view('supplies.inks.edit', compact('ink'));
    }

    public function update(InkRequest $request, Ink $ink, InkService $service): RedirectResponse
    {
        $service->update($ink, $request->validated());

        return redirect()->route('inks.show', $ink)->with('success', 'Tinta actualizada correctamente.');
    }

    public function updateQuantity(Request $request, Ink $ink, InkService $service): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $ink);
        $quantity = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
        ])['quantity'];

        $service->update($ink, ['quantity' => $quantity]);

        if ($request->expectsJson()) {
            return response()->json(['quantity' => (int) $quantity]);
        }

        return redirect()->to(route('supplies.index', ['type' => 'inks']) . '#ink-' . $ink->id)
            ->with('success', 'Existencia actualizada correctamente.');
    }

    public function destroy(Ink $ink, InkService $service): RedirectResponse
    {
        $this->authorize('delete', $ink);
        $service->delete($ink);

        return redirect()->route('supplies.index', ['type' => 'inks'])->with('success', 'Tinta eliminada correctamente.');
    }
}
