<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanConnectionFactory;
use Enqueue\Gearman\Tests\SkipIfGearmanExtensionIsNotInstalledTrait;
use Enqueue\Psr\Spec\PsrConnectionFactorySpec;

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
