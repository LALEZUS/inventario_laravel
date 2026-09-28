<?php

namespace App\Services;

use App\Models\EnterpriseNetwork;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EnterpriseNetworkService
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    public function store(array $data, ?User $user = null): EnterpriseNetwork
    {
        return DB::transaction(function () use ($data) {
            $network = EnterpriseNetwork::create($data);
            $this->audit->record('create', 'enterprise_networks', $network->id, null, $network->getAttributes());

            return $network;
        });
    }

    public function update(EnterpriseNetwork $enterpriseNetwork, array $data, ?User $user = null): EnterpriseNetwork
    {
        $before = $enterpriseNetwork->getAttributes();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return DB::transaction(function () use ($enterpriseNetwork, $data, $before) {
            $enterpriseNetwork->update($data);
            $fresh = $enterpriseNetwork->fresh();
            $this->audit->record('update', 'enterprise_networks', $enterpriseNetwork->id, $before, $fresh->getAttributes());

            return $fresh;
        });
    }

    public function delete(EnterpriseNetwork $enterpriseNetwork, ?User $user = null): void
    {
        $before = $enterpriseNetwork->getAttributes();

        DB::transaction(function () use ($enterpriseNetwork, $before) {
            $enterpriseNetwork->delete();
            $this->audit->record('delete', 'enterprise_networks', $enterpriseNetwork->id, $before, null);
        });
    }
}