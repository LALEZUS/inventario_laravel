<?php

namespace App\Policies;

use App\Models\AccountCredential;
use App\Models\User;

class AccountCredentialPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'soporte', 'consulta'], true); }
    public function view(User $user, AccountCredential $credential): bool { return $this->viewAny($user); }
    public function viewSensitive(User $user, AccountCredential $credential): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function create(User $user): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function update(User $user, AccountCredential $credential): bool { return $this->create($user); }
    public function delete(User $user, AccountCredential $credential): bool { return $user->role === 'admin'; }
    public function viewAudit(User $user, AccountCredential $credential): bool { return $user->role === 'admin'; }
}
