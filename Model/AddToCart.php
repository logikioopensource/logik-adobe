<?php
namespace Logik\Logik\Model;

use Logik\Logik\Api\AddToCartInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;
use function PHPUnit\Framework\isNull;

class AddToCart implements AddToCartInterface
{
    protected $cartRepository;
    protected $logger;
    protected $productRepository;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    public function addItems(int $quoteId, array $items): array
    {
        $errors = [];
        $quote = $this->cartRepository->getActive($quoteId);
        foreach ($items as $item) {
            try {
                $this->logger->info("Adding Item: " . $item->getSku() . " with price: " . $item->getPrice());
                $sku = $item->getSku();
                $price = $item->getPrice();                

                $product = $this->productRepository->get($sku);
                // Handle Custom Options: This may need extending to add Configurable/Bundle/Grouped products with options
                $options = [];
                if ($item->getProductOption() !== null && $item->getProductOption()->getExtensionAttributes() !== null) {
                    foreach ($item->getProductOption()->getExtensionAttributes()->getCustomOptions() as $customOption) {
                        $options[$customOption->getOptionId()] = $customOption->getOptionValue();
                    }
                }
                // Specify information specific to the cart Item (other than price)
                $request = new \Magento\Framework\DataObject([
                    'qty' => $item->getQty(),
                    'options' => $options
                ]);

                // Add the product to quote, getting the added $quoteItem
                $quoteItem = $quote->addProduct($product, $request);
                // Handle errors adding product
                if (!($quoteItem instanceof \Magento\Quote\Model\Quote\Item)) {
                    // This syntax is kinda ridiculous - why is this how to append to an array
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
                // This ensures that calls to get the cart will have the custom price
                $quote->collectTotals();
                // Save the quote
                $this->cartRepository->save($quote);
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
        return $errors;
    }
}
