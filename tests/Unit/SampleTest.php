<?php

namespace Tests\Unit;

use Q\Models\SimpleModel;
use Tests\TestCase;

class SampleTest extends TestCase
{
    public function testSample()
    {
        $model = new SimpleModel();
        dd($model);
        $this->assertEquals(0, 0);
    }
}
