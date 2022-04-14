<?php
/**
 * @author quantm
 * @date: 4/25/2017 9:37 PM
 */

namespace Q\Exceptions;


class HttpNotFoundException extends \Exception
{
    public function __construct($message = 'Page not found', $code = 0, \Exception $previous = null) {
        // some code
        http_response_code(404);
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

}