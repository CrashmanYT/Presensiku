<?php

namespace App\Contracts;

use App\Models\Device;

interface DeviceRepositoryInterface
{
    /**
     * Get or create device by cloud ID
     */
    public function getOrCreateByCloudId(string $cloudId): Device;

    /**
     * Find device by cloud ID
     */
    public function findByCloudId(string $cloudId): ?Device;
}
