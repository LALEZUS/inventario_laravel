<?php
namespace App\Policies;
use App\Models\OfficeEmail; use App\Models\User;
class OfficeEmailPolicy
{
    public function viewAny(User $u): bool { return in_array($u->role,['admin','soporte','consulta'],true); }
    public function view(User $u, OfficeEmail $m): bool { return $this->viewAny($u); }
    public function viewSensitive(User $u, OfficeEmail $m): bool { return in_array($u->role,['admin','soporte'],true); }
    public function create(User $u): bool { return in_array($u->role,['admin','soporte'],true); }
    public function update(User $u, OfficeEmail $m): bool { return $this->create($u); }
    public function delete(User $u, OfficeEmail $m): bool { return $u->role==='admin'; }
    public function viewAudit(User $u, OfficeEmail $m): bool { return $u->role==='admin'; }
}
