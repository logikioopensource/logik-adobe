<?php
namespace Logik\Logik\Exception;

use Magento\Framework\Exception\AbstractAggregateException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
class LogikCartException extends AbstractAggregateException
{
    protected $errors;

    public function __construct($message, array $errors = [], $code = 400)
    {
        parent::__construct(
            new Phrase($message),
            null,
            $code,
        );
        
        foreach( $errors as $error ) {
            // print_r(value: $errors);
            $this->errors[] = new LocalizedException(new Phrase("An error occurred adding item %1 to cart %2", [$error['sku'], $error['message']]));
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
