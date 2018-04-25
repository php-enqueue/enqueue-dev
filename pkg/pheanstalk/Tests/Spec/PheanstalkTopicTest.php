<?php

namespace Enqueue\Pheanstalk\Tests\Spec;

use Enqueue\Pheanstalk\PheanstalkDestination;
use Interop\Queue\Spec\PsrTopicSpec;

class PheanstalkTopicTest extends PsrTopicSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createTopic()
    {
        return new PheanstalkDestination(self::EXPECTED_TOPIC_NAME);
    }
}
