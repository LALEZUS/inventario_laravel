<?php

namespace App\Services;

use App\Models\User;
use App\Models\WatchguardUser;
use Illuminate\Support\Facades\DB;

class WatchguardUserService
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    public function store(array $data, ?User $user = null): WatchguardUser
    {
        return DB::transaction(function () use ($data) {
            $watchguard = WatchguardUser::create($data);
            $this->audit->record('create', 'watchguard_users', $watchguard->id, null, $watchguard->getAttributes());

            return $watchguard;
        });
    }

    public function update(WatchguardUser $watchguardUser, array $data, ?User $user = null): WatchguardUser
    {
        $before = $watchguardUser->getAttributes();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return DB::transaction(function () use ($watchguardUser, $data, $before) {
            $watchguardUser->update($data);
            $fresh = $watchguardUser->fresh();
            $this->audit->record('update', 'watchguard_users', $watchguardUser->id, $before, $fresh->getAttributes());

            return $fresh;
        });
    }

    public function delete(WatchguardUser $watchguardUser, ?User $user = null): void
    {
        $before = $watchguardUser->getAttributes();

        DB::transaction(function () use ($watchguardUser, $before) {
            $watchguardUser->delete();
            $this->audit->record('delete', 'watchguard_users', $watchguardUser->id, $before, null);
        });
    }
}