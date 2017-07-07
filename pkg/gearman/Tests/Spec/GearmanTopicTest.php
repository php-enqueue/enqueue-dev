<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanDestination;
use Enqueue\Gearman\Tests\SkipIfGearmanExtensionIsNotInstalledTrait;
use Interop\Queue\Spec\PsrTopicSpec;

class GearmanTopicTest extends PsrTopicSpec
{
    use SkipIfGearmanExtensionIsNotInstalledTrait;

    /**
     * {@inheritdoc}
     */
    protected function createTopic()
    {
        return new GearmanDestination(self::EXPECTED_TOPIC_NAME);
    }
}
