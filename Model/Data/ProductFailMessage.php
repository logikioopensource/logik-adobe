<?php
namespace Logik\Integration\Model;

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

    public function getSku()
    {
        return $this->sku;
    }

    public function setSku($sku)
    {
        $this->sku = $sku;
        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
}
