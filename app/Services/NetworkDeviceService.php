<?php

namespace App\Services;

use App\Models\NetworkDevice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NetworkDeviceService
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    public function store(array $data, ?User $user = null): NetworkDevice
    {
        return DB::transaction(function () use ($data) {
            $device = NetworkDevice::create($data);
            $this->audit->record('create', 'network_devices', $device->id, null, $device->getAttributes());

            return $device;
        });
    }

    public function update(NetworkDevice $device, array $data, ?User $user = null): NetworkDevice
    {
        $before = $device->getAttributes();

        return DB::transaction(function () use ($device, $data, $before) {
            $device->update($data);
            $fresh = $device->fresh();
            $this->audit->record('update', 'network_devices', $device->id, $before, $fresh->getAttributes());

            return $fresh;
        });
    }

    public function delete(NetworkDevice $device, ?User $user = null): void
    {
        $before = $device->getAttributes();

        DB::transaction(function () use ($device, $before) {
            $device->delete();
            $this->audit->record('delete', 'network_devices', $device->id, $before, null);
        });
    }
}