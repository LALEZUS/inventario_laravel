<?php

namespace App\Policies;

use App\Models\Cellphone;
use App\Models\User;

class CellphonePolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'soporte', 'consulta'], true); }
    public function view(User $user, Cellphone $cellphone): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function update(User $user, Cellphone $cellphone): bool { return $this->create($user); }
    public function delete(User $user, Cellphone $cellphone): bool { return $user->role === 'admin'; }
    public function upload(User $user, Cellphone $cellphone): bool { return $this->update($user, $cellphone); }
    public function shareSensitive(User $user, Cellphone $cellphone): bool { return $this->update($user, $cellphone); }
    public function viewSensitive(User $user, Cellphone $cellphone): bool { return $this->update($user, $cellphone); }
    public function viewAudit(User $user, Cellphone $cellphone): bool { return $user->role === 'admin'; }
}
