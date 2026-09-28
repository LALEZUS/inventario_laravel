<?php
namespace App\Policies;
use App\Models\EmailBackup; use App\Models\User;
class EmailBackupPolicy
{
    public function viewAny(User $u): bool { return in_array($u->role,['admin','soporte','consulta'],true); }
    public function view(User $u, EmailBackup $m): bool { return $this->viewAny($u); }
    public function create(User $u): bool { return in_array($u->role,['admin','soporte'],true); }
    public function update(User $u, EmailBackup $m): bool { return $this->create($u); }
    public function delete(User $u, EmailBackup $m): bool { return $u->role==='admin'; }
    public function viewAudit(User $u, EmailBackup $m): bool { return $u->role==='admin'; }
}
