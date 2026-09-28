<?php

namespace App\Policies;

use App\Models\OutlookAccount;
use App\Models\User;

class OutlookAccountPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'soporte', 'consulta'], true); }
    public function view(User $user, OutlookAccount $account): bool { return $this->viewAny($user); }
    public function viewSensitive(User $user, OutlookAccount $account): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function create(User $user): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function update(User $user, OutlookAccount $account): bool { return $this->create($user); }
    public function delete(User $user, OutlookAccount $account): bool { return $user->role === 'admin'; }
    public function viewAudit(User $user, OutlookAccount $account): bool { return $user->role === 'admin'; }
}
