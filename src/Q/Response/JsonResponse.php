<?php
/**
 * @author quantm
 * @date: 2/2/2017 5:49 PM
 */

namespace Q\Response;


use Q\Interfaces\Response;

class JsonResponse implements Response
{
    private $data;
    private $encoding;
    public function __construct($data, $encoding = 'utf-8')
    {
        $this->data = $data;
        $this->encoding = $encoding;
    }

    public function render() {
        header('Content-Type: application/json;charset=' . $this->encoding);
        echo json_encode($this->data);
    }

    public function getData() {
        return $this->data;
    }

}