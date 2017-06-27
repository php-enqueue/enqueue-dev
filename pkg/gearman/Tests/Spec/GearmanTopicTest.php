<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanDestination;
use Enqueue\Psr\Spec\PsrTopicSpec;

class GearmanTopicTest extends PsrTopicSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createTopic()
    {
        return new GearmanDestination(self::EXPECTED_TOPIC_NAME);
    }
}
