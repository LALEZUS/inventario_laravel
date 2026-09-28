<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WatchguardUser;

class WatchguardUserPolicy
{
    public function viewAny(User $user): bool { return in_array($user->role, ['admin', 'soporte', 'consulta'], true); }
    public function view(User $user, WatchguardUser $watchguardUser): bool { return $this->viewAny($user); }
    public function viewSensitive(User $user, WatchguardUser $watchguardUser): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function create(User $user): bool { return in_array($user->role, ['admin', 'soporte'], true); }
    public function update(User $user, WatchguardUser $watchguardUser): bool { return $this->create($user); }
    public function delete(User $user, WatchguardUser $watchguardUser): bool { return $user->role === 'admin'; }
    public function viewAudit(User $user, WatchguardUser $watchguardUser): bool { return $user->role === 'admin'; }
}
