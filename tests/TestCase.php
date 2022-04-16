<?php

namespace Tests;

class TestCase extends \PHPUnit\Framework\TestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        setRootPath(__DIR__);
        parent::__construct($name, $data, $dataName);
    }
}
