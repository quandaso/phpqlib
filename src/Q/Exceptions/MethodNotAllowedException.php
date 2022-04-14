<?php
/**
 * @author quantm
 * @date: 4/25/2017 9:37 PM
 */

namespace Q\Exceptions;


class MethodNotAllowedException extends \Exception
{
    public function __construct($message = 'Method not allow', $code = 0, \Exception $previous = null) {
        // some code
        http_response_code(405);
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

}