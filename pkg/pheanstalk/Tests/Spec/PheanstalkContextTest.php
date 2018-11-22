<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkContext;
use Interop\Queue\Spec\ContextSpec;
use Pheanstalk\Pheanstalk;

class PheanstalkContextTest extends ContextSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return new PheanstalkContext($this->createMock(Pheanstalk::class));
    }
}
