<?php
namespace Logik\Integration\Api;

interface AddToCartInterface
{
/**
 * Add items to quote by ID
 *
 * @param int $quoteId
 * @param \Magento\Quote\Api\Data\CartItemInterface[] $items
 * @return \Logik\Integration\Api\Data\ProductFailMessageInterface[] List of errors keyed by SKU
 */
    public function addItems(int $quoteId, array $items);
}
