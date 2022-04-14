<?php
/**
 * @author quantm
 * @date: 2/2/2017 5:48 PM
 */

namespace Q\Response;

use Q\Interfaces\Response;
class RedirectResponse implements Response
{

    private $uri;
    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    public function render() {
        header('Location:' . $this->uri);
        die;
    }
}