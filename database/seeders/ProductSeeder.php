<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds 1,000,000 products, each in a store and about 1 in 5 also from a supplier,
 * each with one default stock row and one category.
 *
 * Uses raw bulk inserts instead of Eloquent factories: building a million models
 * would take hours and exhaust memory. Run it on its own:
 *   php artisan db:seed --class=ProductSeeder
 */
class ProductSeeder extends Seeder
{
    private const TOTAL_PRODUCTS = 1_000_000;

    /**
     * Rows per INSERT. products has 31 columns, so 1000 rows stays well under
     * MySQL's 65,535 placeholder limit.
     */
    private const CHUNK_SIZE = 1000;

    private const BRAND_COUNT = 200;

    private const CATEGORY_COUNT = 300;

    private const STORE_COUNT = 50;

    private const SUPPLIER_COUNT = 30;

    /**
     * One in this many products is also sourced from a supplier.
     */
    private const SUPPLIER_PRODUCT_RATIO = 5;

    private const TRANSACTION_TYPES = ['MARGIN', 'COMMISSION'];

    /**
     * created_at is spread over this window so date-range queries have realistic data.
     */
    private const CREATED_WITHIN_SECONDS = 60 * 60 * 24 * 730;

    private const ADJECTIVES = ['Premium', 'Classic', 'Smart', 'Eco', 'Ultra', 'Compact', 'Pro', 'Deluxe', 'Portable', 'Wireless', 'Organic', 'Vintage'];

    private const NOUNS = ['Headphones', 'Backpack', 'T-Shirt', 'Blender', 'Watch', 'Sneakers', 'Lamp', 'Keyboard', 'Water Bottle', 'Jacket', 'Speaker', 'Notebook', 'Sunglasses', 'Mug', 'Charger'];

    private const UNITS = ['pcs', 'kg', 'box', 'pack', 'set'];

    private const SHIPPING_COST_TYPES = ['free', 'fixed', 'quantityMultiply'];

    public function run(): void
    {
        // Laravel keeps every query in memory by default, which would grow without bound here.
        DB::disableQueryLog();

        $brandIds = $this->seedBrands();
        $categoryIds = $this->seedCategories();
        $stores = $this->seedStores();
        $suppliers = $this->seedSuppliers();

        // Keeps slugs unique per store when the seeder is run more than once.
        $runTag = Str::lower(Str::random(5));

        $output = $this->command?->getOutput();
        $output?->progressStart(self::TOTAL_PRODUCTS);

        for ($offset = 0; $offset < self::TOTAL_PRODUCTS; $offset += self::CHUNK_SIZE) {
            $size = min(self::CHUNK_SIZE, self::TOTAL_PRODUCTS - $offset);
            $products = [];
            $stocks = [];
            $productCategories = [];

            for ($i = 0; $i < $size; $i++) {
                $number = $offset + $i + 1;
                // Ordered UUIDs append to the InnoDB clustered index instead of splitting pages.
                $productId = (string) Str::orderedUuid();
                $name = self::ADJECTIVES[array_rand(self::ADJECTIVES)].' '.self::NOUNS[array_rand(self::NOUNS)].' '.$number;
                $createdAt = date('Y-m-d H:i:s', time() - mt_rand(0, self::CREATED_WITHIN_SECONDS));

                $products[] = $this->productRow($productId, $name, $number, $runTag, $createdAt, $brandIds, $stores, $suppliers);
                $stocks[] = $this->stockRow($productId, $number, $runTag);
                $productCategories[] = [
                    'product_id' => $productId,
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                ];
            }

            DB::transaction(function () use ($products, $stocks, $productCategories) {
                DB::table('products')->insert($products);
                DB::table('product_stocks')->insert($stocks);
                DB::table('category_product')->insert($productCategories);
            });

            $output?->progressAdvance($size);
        }

        $output?->progressFinish();
    }

    /**
     * @return list<string>
     */
    private function seedBrands(): array
    {
        if (DB::table('brands')->exists()) {
            return DB::table('brands')->pluck('id')->all();
        }

        $now = now();
        $rows = [];

        for ($i = 1; $i <= self::BRAND_COUNT; $i++) {
            $rows[] = [
                'id' => (string) Str::orderedUuid(),
                'name' => "Brand {$i}",
                'slug' => "brand-{$i}",
                'is_verified' => mt_rand(0, 1) === 1,
                'is_active' => true,
                'is_popular' => mt_rand(1, 10) === 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('brands')->insert($rows);

        return array_column($rows, 'id');
    }

    /**
     * Creates one owner user per store.
     *
     * @return array<string, string> store id => "Store name (Owner name)"
     */
    private function seedStores(): array
    {
        if (DB::table('stores')->exists()) {
            return DB::table('stores')
                ->leftJoin('users', 'users.id', '=', 'stores.user_id')
                ->get(['stores.id', 'stores.store_name', 'users.name as owner_name'])
                ->mapWithKeys(fn ($store) => [$store->id => $this->storeLabel($store->store_name, $store->owner_name)])
                ->all();
        }

        $now = now();
        // Hashing is slow, so every owner shares one hash.
        $password = Hash::make('12345678');
        $owners = [];

        for ($i = 1; $i <= self::STORE_COUNT; $i++) {
            $owners[] = [
                'name' => "Owner {$i}",
                'email' => "owner{$i}@example.com",
                'password' => $password,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Upsert so a rerun after a partial seed does not hit the unique email index.
        DB::table('users')->upsert($owners, ['email'], ['name', 'updated_at']);
        $ownerIds = DB::table('users')->whereIn('email', array_column($owners, 'email'))->pluck('id', 'email');

        $rows = [];
        $labels = [];

        foreach ($owners as $index => $owner) {
            $i = $index + 1;
            $id = (string) Str::orderedUuid();
            $rows[] = [
                'id' => $id,
                'user_id' => $ownerIds[$owner['email']],
                'store_name' => "Store {$i}",
                'is_online' => true,
                'is_approved' => true,
                'is_active' => true,
                'store_type' => mt_rand(0, 1) === 1 ? 'INTERNAL' : 'EXTERNAL',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $labels[$id] = $this->storeLabel("Store {$i}", $owner['name']);
        }

        DB::table('stores')->insert($rows);

        return $labels;
    }

    private function storeLabel(string $storeName, ?string $ownerName): string
    {
        return $ownerName === null ? $storeName : "{$storeName} ({$ownerName})";
    }

    /**
     * @return array<string, string> supplier id => supplier name
     */
    private function seedSuppliers(): array
    {
        if (DB::table('suppliers')->exists()) {
            return DB::table('suppliers')->pluck('name', 'id')->all();
        }

        $now = now();
        $rows = [];

        for ($i = 1; $i <= self::SUPPLIER_COUNT; $i++) {
            $rows[] = [
                'id' => (string) Str::orderedUuid(),
                'name' => "Supplier {$i}",
                'slug' => "supplier-{$i}",
                'email' => "supplier{$i}@example.com",
                'transaction_percentage' => mt_rand(500, 2500) / 100,
                'transaction_type' => self::TRANSACTION_TYPES[array_rand(self::TRANSACTION_TYPES)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('suppliers')->insert($rows);

        return array_column($rows, 'name', 'id');
    }

    /**
     * Creates a two-level tree: the first tenth are roots, the rest are their children.
     *
     * @return list<string>
     */
    private function seedCategories(): array
    {
        if (DB::table('categories')->exists()) {
            return DB::table('categories')->pluck('id')->all();
        }

        $now = now();
        $rootCount = intdiv(self::CATEGORY_COUNT, 10);
        $rows = [];

        for ($i = 1; $i <= self::CATEGORY_COUNT; $i++) {
            $rows[] = [
                'id' => (string) Str::orderedUuid(),
                'name' => "Category {$i}",
                'slug' => "category-{$i}",
                'parent_id' => $i > $rootCount ? $rows[mt_rand(0, $rootCount - 1)]['id'] : null,
                'is_active' => true,
                'is_popular' => mt_rand(1, 10) === 1,
                'is_returnable' => mt_rand(0, 1) === 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('categories')->insert($rows);

        return array_column($rows, 'id');
    }

    /**
     * @param  list<string>  $brandIds
     * @param  array<string, string>  $stores  store id => "Store name (Owner name)"
     * @param  array<string, string>  $suppliers  supplier id => supplier name
     */
    private function productRow(string $id, string $name, int $number, string $runTag, string $createdAt, array $brandIds, array $stores, array $suppliers): array
    {
        // Every product is listed in a store; some are also sourced from a supplier.
        // The name gets the store (and owner) as a suffix, then the supplier if any.
        $storeId = array_rand($stores);
        $name .= ' - '.$stores[$storeId];

        $supplierId = null;
        if (mt_rand(1, self::SUPPLIER_PRODUCT_RATIO) === 1) {
            $supplierId = array_rand($suppliers);
            $name .= ' - '.$suppliers[$supplierId];
        }
        $shippingCostType = self::SHIPPING_COST_TYPES[array_rand(self::SHIPPING_COST_TYPES)];

        return [
            'id' => $id,
            'bin_no' => sprintf('BIN-%s-%07d', $runTag, $number),
            'name' => $name,
            'slug' => Str::slug($name).'-'.$runTag,
            'unit' => self::UNITS[array_rand(self::UNITS)],
            'store_id' => $storeId,
            'supplier_id' => $supplierId,
            'brand_id' => $brandIds[array_rand($brandIds)],
            'short_description' => "Short description for {$name}.",
            'description' => "Full description for {$name}.",
            'low_stock' => mt_rand(1, 20),
            'minimum_order_qty' => mt_rand(1, 5),
            'is_digital' => mt_rand(1, 20) === 1,
            'is_fragile' => mt_rand(1, 10) === 1,
            'is_rotting' => mt_rand(1, 20) === 1,
            'is_special' => mt_rand(1, 10) === 1,
            'is_published' => mt_rand(1, 10) !== 1,
            'is_active' => mt_rand(1, 20) !== 1,
            'is_dropshipper_product' => mt_rand(1, 4) === 1,
            'length' => mt_rand(100, 10000) / 100,
            'height' => mt_rand(100, 10000) / 100,
            'width' => mt_rand(100, 10000) / 100,
            'weight' => mt_rand(10, 5000) / 100,
            'shipping_cost' => $shippingCostType === 'free' ? 0 : mt_rand(50, 300),
            'shipping_cost_type' => $shippingCostType,
            'shipping_cost_dropshipper' => $shippingCostType === 'free' ? 0 : mt_rand(50, 300),
            'shipping_cost_type_dropshipper' => $shippingCostType,
            'note_for_seller' => '[]',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    private function stockRow(string $productId, int $number, string $runTag): array
    {
        $purchasePrice = mt_rand(100, 100000) / 100;
        $margin = mt_rand(10, 60);
        $salesPrice = round($purchasePrice * (1 + $margin / 100), 2);
        $hasDiscount = mt_rand(1, 4) === 1;

        return [
            'id' => (string) Str::orderedUuid(),
            'product_id' => $productId,
            'sku' => sprintf('SKU-%s-%07d', $runTag, $number),
            'attribute_value' => null,
            'attribute' => null,
            'purchase_price' => $purchasePrice,
            'margin' => $margin,
            'sales_price' => $salesPrice,
            'discount' => $hasDiscount ? mt_rand(5, 30) : null,
            'discount_type' => $hasDiscount ? 'percent' : null,
            'discount_start_date' => $hasDiscount ? now()->subDays(mt_rand(0, 30))->toDateString() : null,
            'discount_end_date' => $hasDiscount ? now()->addDays(mt_rand(1, 60))->toDateString() : null,
            'dropshipper_price' => round($salesPrice * 0.9, 2),
            'dropshipper_discount' => null,
            'dropshipper_discount_type' => null,
            'dropshipper_discount_start_date' => null,
            'dropshipper_discount_end_date' => null,
            'stock_qty' => mt_rand(0, 500),
            'thumbnail_image' => null,
            'gallery_images' => '[]',
            'is_default' => true,
            'is_active' => true,
        ];
    }
}
