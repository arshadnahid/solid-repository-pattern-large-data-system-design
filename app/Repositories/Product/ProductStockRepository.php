<?php

namespace App\Repositories\Product;

use App\DTOs\Product\ProductStockDTO;
use App\Repositories\Product\ProductInterfaces\ProductStockRepositoryInterface;
use Illuminate\Database\ConnectionInterface;

class ProductStockRepository implements ProductStockRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function findManyForOrder(array $ids): array
    {
        // One query for the whole cart, however many lines it has.
        return $this->db->table('product_stocks')
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->leftJoin('stores', 'stores.id', '=', 'products.store_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'products.supplier_id')
            ->whereIn('product_stocks.id', $ids)
            ->get([
                'product_stocks.*',
                'products.name as product_name',
                'products.is_active as product_is_active',
                'products.is_published as product_is_published',
                'products.store_id',
                'products.supplier_id',
                'products.shipping_cost',
                'products.shipping_cost_type',
                'stores.store_type',
                'suppliers.transaction_type as supplier_transaction_type',
                'suppliers.transaction_percentage as supplier_transaction_percentage',
            ])
            ->mapWithKeys(fn (object $row) => [$row->id => ProductStockDTO::fromRow($row)])
            ->all();
    }

    public function reserve(string $id, int $qty): bool
    {
        // A single conditional UPDATE: the row lock lasts one statement and
        // two buyers can never both take the last unit.
        return $this->db->table('product_stocks')
            ->where('id', $id)
            ->where('is_active', true)
            ->where('stock_qty', '>=', $qty)
            ->decrement('stock_qty', $qty) === 1;
    }

    public function release(string $id, int $qty): void
    {
        $this->db->table('product_stocks')->where('id', $id)->increment('stock_qty', $qty);
    }
}
