<?php
namespace App\Policies;
use App\Models\MicrosoftEmail; use App\Models\User;
class MicrosoftEmailPolicy
{
    public function viewAny(User $u): bool { return in_array($u->role,['admin','soporte','consulta'],true); }
    public function view(User $u, MicrosoftEmail $m): bool { return $this->viewAny($u); }
    public function viewSensitive(User $u, MicrosoftEmail $m): bool { return in_array($u->role,['admin','soporte'],true); }
    public function create(User $u): bool { return in_array($u->role,['admin','soporte'],true); }
    public function update(User $u, MicrosoftEmail $m): bool { return $this->create($u); }
    public function delete(User $u, MicrosoftEmail $m): bool { return $u->role==='admin'; }
    public function viewAudit(User $u, MicrosoftEmail $m): bool { return $u->role==='admin'; }
}
