<?php

namespace App\Repositories\Coupon;

use App\DTOs\Coupon\ActiveCouponDTO;
use App\Repositories\Coupon\CouponInterfaces\CouponRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

class CouponRepository implements CouponRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function findActive(string $code, ?string $storeId, ?string $supplierId): ?ActiveCouponDTO
    {
        $now = now();

        $row = $this->db->table('coupons')
            ->where('code', $code)
            ->where('is_active', true)
            ->where(fn (Builder $q) => $storeId ? $q->where('store_id', $storeId) : $q->whereNull('store_id'))
            ->where(fn (Builder $q) => $supplierId ? $q->where('supplier_id', $supplierId) : $q->whereNull('supplier_id'))
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
            ->where(fn (Builder $q) => $this->hasUsesLeft($q))
            ->first();

        return $row ? ActiveCouponDTO::fromRow($row) : null;
    }

    public function reserveUsage(string $id): bool
    {
        return $this->db->table('coupons')
            ->where('id', $id)
            ->where(fn (Builder $q) => $this->hasUsesLeft($q))
            ->increment('used_count') === 1;
    }

    public function releaseUsage(string $id): void
    {
        $this->db->table('coupons')->where('id', $id)->where('used_count', '>', 0)->decrement('used_count');
    }

    private function hasUsesLeft(Builder $query): Builder
    {
        return $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
    }
}
