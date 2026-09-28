<?php

namespace App\Policies;

use App\Models\NetworkDevice;
use App\Models\User;

class NetworkDevicePolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'soporte', 'consulta'], true); }
    public function view(User $user, NetworkDevice $device): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function update(User $user, NetworkDevice $device): bool { return $this->create($user); }
    public function delete(User $user, NetworkDevice $device): bool { return $user->role === 'admin'; }
    public function viewAudit(User $user, NetworkDevice $device): bool { return $user->role === 'admin'; }
}
