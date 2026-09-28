<?php

namespace App\Policies;

use App\Models\Ink;
use App\Models\User;

class InkPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'soporte', 'consulta'], true); }
    public function view(User $user, Ink $ink): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function update(User $user, Ink $ink): bool { return $this->create($user); }
    public function delete(User $user, Ink $ink): bool { return $user->role === 'admin'; }
    public function viewAudit(User $user, Ink $ink): bool { return $user->role === 'admin'; }
}
