<?php

namespace App\Policies;

use App\Models\Printer;
use App\Models\User;

class PrinterPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'soporte', 'consulta'], true); }
    public function view(User $user, Printer $printer): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function update(User $user, Printer $printer): bool { return $this->create($user); }
    public function delete(User $user, Printer $printer): bool { return $user->role === 'admin'; }
    public function upload(User $user, Printer $printer): bool { return $this->update($user, $printer); }
    public function viewAudit(User $user, Printer $printer): bool { return $user->role === 'admin'; }
}
