<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanConnectionFactory;
use Enqueue\Gearman\Tests\SkipIfGearmanExtensionIsNotInstalledTrait;
use Interop\Queue\Spec\ConnectionFactorySpec;

class GearmanConnectionFactoryTest extends ConnectionFactorySpec
{
    use SkipIfGearmanExtensionIsNotInstalledTrait;

    /**
     * {@inheritdoc}
     */
    protected function createConnectionFactory()
    {
        return new GearmanConnectionFactory();
    }
}
