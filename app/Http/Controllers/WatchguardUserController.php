<?php

namespace App\Http\Controllers;

use App\Http\Requests\WatchguardUserRequest;
use App\Models\AuditLog;
use App\Models\WatchguardUser;
use App\Services\WatchguardUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WatchguardUserController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', WatchguardUser::class);

        return view('network.watchguard.create', ['watchguardUser' => new WatchguardUser]);
    }

    public function store(WatchguardUserRequest $request, WatchguardUserService $service): RedirectResponse
    {
        $user = $service->store($request->validated());

        return redirect()->route('watchguard-users.show', $user)->with('success', 'Usuario WatchGuard registrado.');
    }

    public function show(WatchguardUser $watchguardUser): View
    {
        $this->authorize('view', $watchguardUser);
        $auditLogs = request()->user()->can('viewAudit', $watchguardUser)
            ? AuditLog::where('entity', 'watchguard_users')->where('entity_id', (string) $watchguardUser->id)->latest('created_at')->limit(20)->get()
            : collect();

        return view('network.watchguard.show', compact('watchguardUser', 'auditLogs'));
    }

    public function edit(WatchguardUser $watchguardUser): View
    {
        $this->authorize('update', $watchguardUser);

        return view('network.watchguard.edit', compact('watchguardUser'));
    }

    public function update(WatchguardUserRequest $request, WatchguardUser $watchguardUser, WatchguardUserService $service): RedirectResponse
    {
        $service->update($watchguardUser, $request->validated());

        return redirect()->route('watchguard-users.show', $watchguardUser)->with('success', 'Usuario WatchGuard actualizado.');
    }

    public function destroy(WatchguardUser $watchguardUser, WatchguardUserService $service): RedirectResponse
    {
        $this->authorize('delete', $watchguardUser);
        $service->delete($watchguardUser);

        return redirect()->route('network.index', ['type' => 'watchguard'])->with('success', 'Usuario WatchGuard eliminado.');
    }
}