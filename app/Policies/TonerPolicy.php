<?php

namespace App\Policies;

use App\Models\Toner;
use App\Models\User;

class TonerPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'soporte', 'consulta'], true); }
    public function view(User $user, Toner $toner): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function update(User $user, Toner $toner): bool { return $this->create($user); }
    public function delete(User $user, Toner $toner): bool { return $user->role === 'admin'; }
    public function viewAudit(User $user, Toner $toner): bool { return $user->role === 'admin'; }
}
