<?php

namespace Enqueue\Test;

use Enqueue\Consumption\OnStartContext;

trait ConsumptionContextMockTrait
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|OnStartContext
     */
    public function createOnStartContextMock()
    {
        return $this->createMock(OnStartContext::class);
    }
}
