<?php

namespace Enqueue\Bundle\Tests\Functional\App;

class TestAsyncListener
{
    public $calls = [];

    public function onEvent()
    {
        $this->calls[] = func_get_args();
    }
}
