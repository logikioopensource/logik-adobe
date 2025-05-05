<?php
namespace Logik\Logik\Model;

use Logik\Logik\Api\AddToCartInterface;
use Logik\Logik\Exception\LogikCartException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Psr\Log\LoggerInterface;

class AddToCart implements AddToCartInterface
{
    private $cartRepository;
    private $logger;
    private $productRepository;
    private $configurable;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        Configurable $configurable,
    ) {
        $this->cartRepository = $cartRepository;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->configurable = $configurable;
    }

    public function addItems(int $quoteId, array $items): array
    {
        $errors = [];
        /** @var \Magento\Quote\Model\Quote */
        $quote = $this->cartRepository->getActive($quoteId);
        foreach ($items as $item) {
            try {
                $this->logger->info("Adding Item: " . $item->getSku() . " with price: " . $item->getPrice());
                $sku = $item->getSku();
                $price = $item->getPrice();                

                // Get Custom Options array
                $options = [];
                if ($item->getProductOption() !== null && $item->getProductOption()->getExtensionAttributes() !== null) {
                    foreach ($item->getProductOption()->getExtensionAttributes()->getCustomOptions() as $customOption) {
                        $options[$customOption->getOptionId()] = $customOption->getOptionValue();
                    }
                }

                /** @var $product \Magento\Catalog\Api\Data\ProductInterface */
                $product = $this->productRepository->get($sku);

                $configurableParentIds = $this->configurable->getParentIdsByChild($product->getId());

                /** @var \Magento\Quote\Model\Quote\Item */
                $quoteItem;
                if (!empty($configurableParentIds)) {
                    $parentId = $configurableParentIds[0];
                    $parentProduct = $this->productRepository->getById($parentId);
                    $quoteItem = $this->addConfigurableProduct($product, $parentProduct, $quote, $options, $item);
                } else {
                    $quoteItem = $this->addSimpleProduct($product, $quote, $options, $item);
                }
                // Handle errors adding product
                if (!($quoteItem instanceof \Magento\Quote\Model\Quote\Item)) {
                    // This syntax is kinda ridiculous - why does this append to an array
                    $errors[] = [
                        'sku' => $sku,
                        'message' => 'Failed to add product to quote with message ' . $quoteItem
                    ];
                    continue;
                }
                // If we have a price, set it and ensure it will be used
                if (($price !== null)) {
                    $quoteItem->setCustomPrice($price);
                    $quoteItem->setOriginalCustomPrice($price);
                    $quoteItem->setPrice($price);
                    $quoteItem->getProduct()->setIsSuperMode(true);
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $errors[] = [
                    'sku' => $item->getSku(),
                    'message' => $e->getMessage()
                ];        
            } catch (\Error $error) {
                $this->logger->error($error->getMessage());
                $errors[] = [
                    'sku' => $item->getSku(),
                    'message' => $error->getMessage()
                ];  
            } 
        }
        // If all items failed
        if (count($errors) >= count($items)) {
            throw new LogikCartException(
                'All items failed to be added to cart.', $errors);
            }
        // This ensures that calls to get the cart will have the custom price
        $quote->collectTotals();
        // Save the quote
        $this->cartRepository->save($quote);
        return $errors;
    }

        /**
     * Adds a configurable product to the given quote, returning the quoteItem (or string in error cases)
     * Adding an item can also throw - we handle thrown errors and string returns essentially the
     * 
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param \Magento\Catalog\Api\Data\ProductInterface $parentProduct
     * @param \Magento\Quote\Model\Quote  $quote
     * @param [] $options
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @return \Magento\Quote\Model\Quote\Item|string
     */
    private function addConfigurableProduct($product, $parentProduct, $quote, $options, $item): \Magento\Quote\Model\Quote\Item {
        /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typeInstance */
        $typeInstance = $parentProduct->getTypeInstance();
        $configurableAttributes = $typeInstance->getConfigurableAttributes($parentProduct);

        $superAttribute = [];

        foreach ($configurableAttributes as $attribute) {
            $attributeId = $attribute->getAttributeId(); // internal ID
            $attributeCode = $attribute->getProductAttribute()->getAttributeCode(); // like 'color', 'size', etc.

            // Get the value that the child (simple product) has for this attribute
            $value = $product->getData($attributeCode);
            
            if ($value !== null) {
                $superAttribute[$attributeId] = $value;
            }
        }
        $request = new \Magento\Framework\DataObject([
            'qty' => $item->getQty(),
            'options' => $options,
            'super_attribute' => $superAttribute
        ]);
        return $quote->addProduct($parentProduct, $request);
    }

    /**
     * Adds a simple product to the given quote, returning the quoteItem (or string in error cases)
     * Adding an item can also throw - we handle thrown errors and string returns essentially the
     * 
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param \Magento\Quote\Model\Quote  $quote
     * @param [] $options
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @return \Magento\Quote\Model\Quote\Item|string
     */
    private function addSimpleProduct($product, $quote, $options, $item) {
        $request = new \Magento\Framework\DataObject([
            'qty' => $item->getQty(),
            'options' => $options,
        ]);

        // Add the product to quote, getting the added $quoteItem
        /** @var \Magento\Quote\Model\Quote\Item */
        return $quote->addProduct($product, $request);
    }
}
