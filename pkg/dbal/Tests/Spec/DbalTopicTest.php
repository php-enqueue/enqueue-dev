<?php

namespace Enqueue\Dbal\Tests\Spec;

use Enqueue\Dbal\DbalDestination;
use Interop\Queue\Spec\PsrTopicSpec;

class DbalTopicTest extends PsrTopicSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createTopic()
    {
        return new DbalDestination(self::EXPECTED_TOPIC_NAME);
    }
}
