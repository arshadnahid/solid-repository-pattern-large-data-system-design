<?php

namespace App\Repositories\Product\ProductInterfaces;

use App\DTOs\Product\ProductStockDTO;

interface ProductStockRepositoryInterface
{
    /**
     * @param  string[]  $ids
     * @return array<string, ProductStockDTO> keyed by stock id; unknown ids are missing.
     */
    public function findManyForOrder(array $ids): array;

    /**
     * Takes $qty off the stock if that much is left. Returns false otherwise.
     */
    public function reserve(string $id, int $qty): bool;

    public function release(string $id, int $qty): void;
}
