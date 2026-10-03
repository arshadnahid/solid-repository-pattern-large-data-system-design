<?php

namespace App\Repositories\Coupon\CouponInterfaces;

use App\DTOs\Coupon\ActiveCouponDTO;

interface CouponRepositoryInterface
{
    public function findActive(string $code, ?string $storeId, ?string $supplierId): ?ActiveCouponDTO;

    /**
     * Counts one use if the coupon still has uses left. Returns false otherwise.
     */
    public function reserveUsage(string $id): bool;

    public function releaseUsage(string $id): void;
}
