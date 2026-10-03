<?php

namespace App\DTOs\Product;

/**
 * A product stock row joined with what ordering it needs from its product,
 * store and supplier. Amounts stay decimal strings as the database returns them.
 */
final class ProductStockDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $productId,
        public readonly string $productName,
        public readonly ?string $sku,
        public readonly ?string $attributeValue,
        public readonly ?string $thumbnailImage,
        public readonly string $purchasePrice,
        public readonly ?string $margin,
        public readonly string $salesPrice,
        public readonly ?string $discount,
        public readonly ?string $discountType,
        public readonly ?string $discountStartDate,
        public readonly ?string $discountEndDate,
        public readonly ?string $dropshipperPrice,
        public readonly ?string $dropshipperDiscount,
        public readonly ?string $dropshipperDiscountType,
        public readonly int $stockQty,
        public readonly bool $isAvailable,
        public readonly ?string $storeId,
        public readonly ?string $storeType,
        public readonly ?string $supplierId,
        public readonly ?string $supplierTransactionType,
        public readonly ?string $supplierTransactionPercentage,
        public readonly ?string $shippingCost,
        public readonly ?string $shippingCostType,
    ) {
    }

    public static function fromRow(object $row): self
    {
        return new self(
            id: $row->id,
            productId: $row->product_id,
            productName: $row->product_name,
            sku: $row->sku,
            attributeValue: $row->attribute_value,
            thumbnailImage: $row->thumbnail_image,
            purchasePrice: (string) $row->purchase_price,
            margin: $row->margin,
            salesPrice: (string) $row->sales_price,
            discount: $row->discount,
            discountType: $row->discount_type,
            discountStartDate: $row->discount_start_date,
            discountEndDate: $row->discount_end_date,
            dropshipperPrice: $row->dropshipper_price,
            dropshipperDiscount: $row->dropshipper_discount,
            dropshipperDiscountType: $row->dropshipper_discount_type,
            stockQty: (int) $row->stock_qty,
            isAvailable: (bool) $row->is_active && (bool) $row->product_is_active && (bool) $row->product_is_published,
            storeId: $row->store_id,
            storeType: $row->store_type,
            supplierId: $row->supplier_id,
            supplierTransactionType: $row->supplier_transaction_type,
            supplierTransactionPercentage: $row->supplier_transaction_percentage,
            shippingCost: $row->shipping_cost,
            shippingCostType: $row->shipping_cost_type,
        );
    }
}
