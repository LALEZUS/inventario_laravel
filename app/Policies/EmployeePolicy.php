<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'soporte', 'consulta'], true); }
    public function view(User $user, Employee $employee): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function update(User $user, Employee $employee): bool { return $this->create($user); }
    public function delete(User $user, Employee $employee): bool { return $user->role === 'admin'; }
    public function viewAudit(User $user, Employee $employee): bool { return $user->role === 'admin'; }
}
