<?php
namespace Logik\Integration\Model\Data;

use Logik\Integration\Api\Data\ProductFailMessageInterface;

class ProductFailMessage implements ProductFailMessageInterface
{
    /**
     * @var string
     */
    protected $sku;

    /**
     * @var string
     */
    protected $message;

    /**
     * Builds error information
     *
     * @param string $sku
     * @param string $message
     */

    public function __construct($sku, $message)
    {
        $this->sku = $sku;
        $this->message = $message;
    }

    /**
     * Summary of getSku
     *
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * Summary of setSku
     *
     * @param string $sku
     * @return static
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
        return $this;
    }

    /**
     * Summary of getMessage
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Summary of setMessage
     *
     * @param string $message
     * @return static
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
}
