<?php
namespace Logik\Integration\Api\Data;

interface ProductFailMessageInterface
{

    /**
     * Summary of __construct
     * @param string $sku
     * @param string $message
     */
    public function __construct($sku, $message);

    /**
     * Get SKU
     *
     * @return string
     */
    public function getSku();

    /**
     * Set SKU
     *
     * @param string $sku
     * @return $this
     */
    public function setSku($sku);

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage();

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message);
}
