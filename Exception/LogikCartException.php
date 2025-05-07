<?php
namespace Logik\Integration\Exception;

use Magento\Framework\Exception\AbstractAggregateException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class LogikCartException extends AbstractAggregateException
{
    protected $errors;

    /** Construct an exception containing all failed products
     * @param string $message Top level message
     * @param array  $errors  Array of errors containing problem SKU and its message
     * @param int    $code    Error code, default: 400
     */
    public function __construct($message, array $errors = [], $code = 400)
    {
        parent::__construct(
            new Phrase($message),
            null,
            $code,
        );
        
        foreach ($errors as $error) {
            // print_r(value: $errors);
            $this->errors[] = new LocalizedException(
                new Phrase(
                    "An error occurred adding item %1 to cart %2",
                    [$error['sku'], $error['message']]
                )
            );
        }
    }

    /**
     * Get Errors attached to this exception, used by exception handlers
     * @return [] array of LocalizedException
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
