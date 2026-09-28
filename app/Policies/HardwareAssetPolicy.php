<?php

namespace App\Policies;

use App\Models\HardwareAsset;
use App\Models\User;

class HardwareAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'soporte', 'consulta'], true);
    }

    public function view(User $user, HardwareAsset $hardwareAsset): bool
    {
        return $this->viewAny($user);
    }

    public function viewSensitive(User $user, HardwareAsset $hardwareAsset): bool
    {
        return in_array($user->role, ['admin', 'soporte'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'soporte'], true);
    }

    public function update(User $user, HardwareAsset $hardwareAsset): bool
    {
        return in_array($user->role, ['admin', 'soporte'], true);
    }

    public function delete(User $user, HardwareAsset $hardwareAsset): bool
    {
        return $user->role === 'admin';
    }

    public function upload(User $user): bool
    {
        return in_array($user->role, ['admin', 'soporte'], true);
    }

    public function viewAudit(User $user): bool
    {
        return $user->role === 'admin';
    }
}
