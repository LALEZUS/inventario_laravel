<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\RecentRecords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);
        $search = trim((string) $request->query('search'));
        $query = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(fn ($query) => $query->where('full_name', 'like', $term)
                    ->orWhere('username', 'like', $term)
                    ->orWhere('role', 'like', $term));
            });

        $users = RecentRecords::apply($query, $request, fn ($query) => $query
            ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'soporte' THEN 1 ELSE 2 END")
            ->orderBy('full_name'))
            ->paginate(20)
            ->withQueryString();

        $roleCounts = User::query()->selectRaw('role, COUNT(*) as total')->groupBy('role')->pluck('total', 'role');

        return view('users.index', compact('users', 'search', 'roleCounts'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', ['managedUser' => new User(['role' => 'consulta'])]);
    }

    public function store(UserRequest $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $user = DB::transaction(function () use ($data, $audit) {
            $user = User::create($data);
            $audit->record('create', 'users', $user->id, null, $user->getAttributes());

            return $user;
        });

        return redirect()->route('users.show', $user)->with('success', 'Usuario creado correctamente.');
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);
        $auditLogs = AuditLog::where('entity', 'users')->where('entity_id', (string) $user->id)
            ->latest('created_at')->limit(20)->get();

        return view('users.show', ['managedUser' => $user, 'auditLogs' => $auditLogs]);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', ['managedUser' => $user]);
    }

    public function update(UserRequest $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validated();
        $this->guardAdministratorRole($request, $user, $data['role']);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $before = $user->getAttributes();
        DB::transaction(function () use ($user, $data, $before, $audit) {
            $user->update($data);
            $audit->record('update', 'users', $user->id, $before, $user->fresh()->getAttributes());
        });

        return redirect()->route('users.show', $user)->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $user);
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'No se puede eliminar al ultimo administrador.');
        }

        $before = $user->getAttributes();
        DB::transaction(function () use ($user, $before, $audit) {
            if (DB::getSchemaBuilder()->hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            $user->delete();
            $audit->record('delete', 'users', $user->id, $before, null);
        });

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }

    private function guardAdministratorRole(Request $request, User $user, string $newRole): void
    {
        if ($request->user()->is($user) && $newRole !== 'admin') {
            throw ValidationException::withMessages(['role' => 'No puedes quitarte tu propio rol de administrador.']);
        }
        if ($user->role === 'admin' && $newRole !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            throw ValidationException::withMessages(['role' => 'Debe existir al menos un administrador.']);
        }
    }
}
