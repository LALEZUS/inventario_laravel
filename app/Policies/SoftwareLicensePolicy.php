<?php

namespace App\Policies;

use App\Models\SoftwareLicense;
use App\Models\User;

class SoftwareLicensePolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'soporte', 'consulta'], true); }
    public function view(User $user, SoftwareLicense $license): bool { return $this->viewAny($user); }
    public function viewSensitive(User $user, SoftwareLicense $license): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function create(User $user): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function update(User $user, SoftwareLicense $license): bool { return $this->create($user); }
    public function delete(User $user, SoftwareLicense $license): bool { return $user->role === 'admin'; }
    public function viewAudit(User $user, SoftwareLicense $license): bool { return $user->role === 'admin'; }
}
