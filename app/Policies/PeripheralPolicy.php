<?php

namespace App\Policies;

use App\Models\Peripheral;
use App\Models\User;

class PeripheralPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'soporte', 'consulta'], true); }
    public function view(User $user, Peripheral $peripheral): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function update(User $user, Peripheral $peripheral): bool { return $this->create($user); }
    public function delete(User $user, Peripheral $peripheral): bool { return $user->role === 'admin'; }
    public function upload(User $user, Peripheral $peripheral): bool { return $this->update($user, $peripheral); }
    public function viewAudit(User $user, Peripheral $peripheral): bool { return $user->role === 'admin'; }
}
