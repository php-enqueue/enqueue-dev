<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkContext;
use Interop\Queue\Spec\PsrContextSpec;
use Pheanstalk\Pheanstalk;

class PheanstalkContextTest extends PsrContextSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return new PheanstalkContext($this->createMock(Pheanstalk::class));
    }
}
