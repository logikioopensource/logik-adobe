<?php
namespace Logik\Integration\Model;

use Logik\Integration\Api\AddToCartInterface;
use Logik\Integration\Exception\LogikCartException;
use Logik\Integration\Model\Data\ProductFailMessage;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Bundle\Model\ResourceModel\Selection\CollectionFactory as SelectionCollectionFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;

class AddToCart implements AddToCartInterface
{
    private $cartRepository;
    private $logger;
    private $productRepository;
    private $configurable;
    private $selectionCollectionFactory;

    /**
     * Summary of __construct
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurable
     * @param \Magento\Bundle\Model\ResourceModel\Selection\CollectionFactory $selectionCollectionFactory
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        Configurable $configurable,
        SelectionCollectionFactory $selectionCollectionFactory
    ) {
        $this->cartRepository = $cartRepository;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->configurable = $configurable;
        $this->selectionCollectionFactory = $selectionCollectionFactory;
    }

    /**
     * Adds the given $items to the quote specified by $quoteId
     *
     * @param int $quoteId
     * @param \Magento\Quote\Api\Data\CartItemInterface[] $items
     * @return \Logik\Integration\Api\Data\ProductFailMessageInterface[] List of errors keyed by SKU
     */
    public function addItems(int $quoteId, array $items): array
    {
        $errors = [];
        /** @var \Magento\Quote\Model\Quote */
        $quote = $this->cartRepository->getActive($quoteId);
        foreach ($items as $item) {
            try {
                $sku = $item->getSku();
                $price = $item->getPrice();
                $this->logger->info("Adding Item: " . $sku . " with price: " . $price);

                // Get Custom Options array
                $options = [];
                $bundleOptions = [];
                $productOption = $item->getProductOption();
                if ($productOption !== null && $productOption->getExtensionAttributes() !== null) {
                    
                    foreach ($item->getProductOption()->getExtensionAttributes()->getCustomOptions() as $customOption) {
                        $options[$customOption->getOptionId()] = $customOption->getOptionValue();
                        if ($customOption->getOptionId() === "bundle_options") {
                            $bundleOptions = json_decode($customOption->getOptionValue(), true);
                        }
                    }
                }

                /** @var $product \Magento\Catalog\Api\Data\ProductInterface */
                $product = $this->productRepository->get($sku);
                $productType = $product->getTypeId();
                $configurableParentIds = $this->configurable->getParentIdsByChild($product->getId());

                /** @var \Magento\Quote\Model\Quote\Item */
                $quoteItem;
                if (!empty($configurableParentIds)) {
                    $parentId = $configurableParentIds[0];
                    $parentProduct = $this->productRepository->getById($parentId);
                    $quoteItem = $this->addConfigurableProduct($product, $quote, $options, $item, $parentProduct);
                } elseif ($productType === Type::TYPE_BUNDLE) {
                    $quoteItem = $this->addBundleProduct($product, $quote, $options, $item, $bundleOptions);
                } else {
                    $quoteItem = $this->addSimpleProduct($product, $quote, $options, $item);
                }
                // Handle errors adding product
                if (!($quoteItem instanceof \Magento\Quote\Model\Quote\Item)) {
                    // This syntax is kinda ridiculous - why does this append to an array
                    $errors[] = new ProductFailMessage(
                        $sku,
                        'Failed to add product to quote with message ' . $quoteItem
                    );
                    continue;
                }
                // If we have a price, set it and ensure it will be used
                // This block does not work for bundles, as bundles set this on their components
                if ($price !== null && $productType !== Type::TYPE_BUNDLE) {
                    $quoteItem->setCustomPrice($price);
                    $quoteItem->setOriginalCustomPrice($price);
                    $quoteItem->setPrice($price);
                    $quoteItem->getProduct()->setIsSuperMode(true);
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $errors[] = new ProductFailMessage($item->getSku(), $e->getMessage());
            } catch (\Error $error) {
                $this->logger->error($error->getMessage());
                $errors[] = new ProductFailMessage($item->getSku(), $error->getMessage());
            }
        }
        // If all items failed
        if (count($errors) === count($items)) {
            throw new LogikCartException(
                'All items failed to be added to cart.',
                $errors
            );
        }
        // This ensures that calls to get the cart will have the custom price
        $quote->collectTotals();

        // Save the quote
        $this->cartRepository->save($quote);
        return $errors;
    }

    /**
     * Adds a configurable product to the given quote, returning the quoteItem (or string in error cases)
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface    $product
     * @param \Magento\Quote\Model\Quote                    $quote
     * @param []                                            $options
     * @param \Magento\Quote\Api\Data\CartItemInterface     $item
     * @param \Magento\Catalog\Api\Data\ProductInterface    $parentProduct
     * @return \Magento\Quote\Model\Quote\Item|string
     */
    private function addConfigurableProduct($product, $quote, $options, $item, $parentProduct):
        \Magento\Quote\Model\Quote\Item|string
    {
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
        $request = new DataObject([
            'qty' => $item->getQty(),
            'options' => $options,
            'super_attribute' => $superAttribute
        ]);
        return $quote->addProduct($parentProduct, $request);
    }

    /**
     * Adds a simple product to the given quote, returning the quoteItem (or string in error cases)
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product bundle product
     * @param \Magento\Quote\Model\Quote                 $quote
     * @param []                                         $options
     * @param \Magento\Quote\Api\Data\CartItemInterface  $item
     * @param []                                         $bundleOptions
     * @return \Magento\Quote\Model\Quote\Item|string
     * @throws LocalizedException Throws a localized exception if any bundle option sku not valid
     */
    private function addBundleProduct($product, $quote, $options, $item, $bundleOptions)
    {
        $bundleSelections = [];
        $bundleOptionQtys = [];

        $skus = array_column($bundleOptions, 'sku');
        $typeInstance = $product->getTypeInstance();
        $optionCollection = $typeInstance->getOptionsIds($product);
        $selectionCollection = $typeInstance->getSelectionsCollection($optionCollection, $product)
            ->addFieldToFilter('sku', ['in' => $skus]);
        $selectionIndex = [];
        // transform so we can look up the selection by the sku its associated with
        foreach ($selectionCollection as $selection) {
            $selectionIndex[$selection->getSku()] = $selection;
        }

        $bundleItemData = [];
        foreach ($bundleOptions as $option) {
            $sku = $option['sku'];
            // If the given sku is not a valid option, we'll throw - this is likely a misconfiguration
            if (!isset($selectionIndex[$sku])) {
                throw new LocalizedException(
                    phrase: __("Bundle option SKU '%1' is not valid for this bundle product.", $sku)
                );
            }
            $selection = $selectionIndex[$sku];
            $optionId = $selection->getOptionId();
            $selectionId = $selection->getSelectionId();
    
            $bundleSelections[$optionId] = $selectionId;
            $bundleOptionQtys[$selectionId] = (int) $option['qty'];
            $bundleItemData[$sku] = [
                'qty' => (int) $option['qty'],
                'price' => (float) $option['price']
            ];
        }

        // Prepare buy request object
        $buyRequest = new DataObject([
            'qty' => $item->getQty(),
            'options' => $options,
            'bundle_option' => $bundleSelections,
            'bundle_option_qty' => $bundleOptionQtys,
        ]);
        // Add product to quote
        $quoteItem = $quote->addProduct($product, $buyRequest);
        if (is_string($quoteItem)) {
            throw new LocalizedException(__("Failed to add product to quote: %1", $quoteItem));
        }
        // Set custom prices on children
        foreach ($quoteItem->getChildren() as $childItem) {
            $data = $bundleItemData[$childItem->getSku()];
            $childItem->setCustomPrice($data['price']);
            $childItem->setOriginalCustomPrice($data['price']);
            $childItem->setPrice($data['price']);
            $childItem->getProduct()->setIsSuperMode(true);
        }
        return $quoteItem;
    }

    /**
     * Adds a simple product to the given quote, returning the quoteItem (or string in error cases)
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface    $product
     * @param \Magento\Quote\Model\Quote                    $quote
     * @param []                                            $options
     * @param \Magento\Quote\Api\Data\CartItemInterface     $item
     * @return \Magento\Quote\Model\Quote\Item|string
     */
    private function addSimpleProduct($product, $quote, $options, $item)
    {
        $request = new DataObject([
            'qty' => $item->getQty(),
            'options' => $options,
        ]);

        // Add the product to quote, getting the added $quoteItem
        /** @var \Magento\Quote\Model\Quote\Item */
        return $quote->addProduct($product, $request);
    }
}
