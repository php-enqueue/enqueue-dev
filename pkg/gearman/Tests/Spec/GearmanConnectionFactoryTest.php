<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanConnectionFactory;
use Enqueue\Gearman\Tests\SkipIfGearmanExtensionIsNotInstalledTrait;
use Interop\Queue\Spec\PsrConnectionFactorySpec;

class GearmanConnectionFactoryTest extends PsrConnectionFactorySpec
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
