<?php

namespace App\Policies;

use App\Models\EnterpriseNetwork;
use App\Models\User;

class EnterpriseNetworkPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'soporte', 'consulta'], true); }
    public function view(User $user, EnterpriseNetwork $network): bool { return $this->viewAny($user); }
    public function viewSensitive(User $user, EnterpriseNetwork $network): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function create(User $user): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function update(User $user, EnterpriseNetwork $network): bool { return $this->create($user); }
    public function delete(User $user, EnterpriseNetwork $network): bool { return $user->role === 'admin'; }
    public function viewAudit(User $user, EnterpriseNetwork $network): bool { return $user->role === 'admin'; }
}
