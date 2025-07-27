<?php

namespace App\Repositories;

use App\Contracts\DeviceRepositoryInterface;
use App\Models\Device;

class DeviceRepository implements DeviceRepositoryInterface
{
    /**
     * Get or create device by cloud ID
     */
    public function getOrCreateByCloudId(string $cloudId): Device
    {
        return Device::firstOrCreate(
            ['cloud_id' => $cloudId],
            [
                'name' => 'Device ' . $cloudId,
                'cloud_id' => $cloudId,
                'ip_address' => 'N/A',
                'is_active' => true,
            ]
        );
    }

    /**
     * Find device by cloud ID
     */
    public function findByCloudId(string $cloudId): ?Device
    {
        return Device::where('cloud_id', $cloudId)->first();
    }
}
