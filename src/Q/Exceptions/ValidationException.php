<?php
/**
 * @author H110
 * @date: 4/28/2017 1:47 PM
 */

namespace Q\Exceptions;


class ValidationException extends \Exception
{
    private $field;

    /**
     * ValidationException constructor.
     * @param string $message
     * @param string $field
     */
    public function __construct($message, $field) {
        // some code
        $this->field  = $field;
        // make sure everything is assigned properly
        parent::__construct($message, 0, null);
    }

    /**
     * @return string
     */
    public function getErrorField() {
        return $this->field;
    }


}