<?php
/**
 * @author quantm
 * @date: 5/10/2017 7:35 PM
 */

namespace Q\Exceptions;


class UnauthorizedException extends \Exception
{
    public function __construct($message = 'Unauthorized', $code = 0, \Exception $previous = null) {
        // some code
        http_response_code(401);
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

}