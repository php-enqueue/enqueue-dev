<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanConnectionFactory;
use Enqueue\Psr\Spec\PsrConnectionFactorySpec;

/**
 * @group functional
 */
class GearmanConnectionFactoryTest extends PsrConnectionFactorySpec
{
    /**
     * {@inheritdoc}
     */
    protected function createConnectionFactory()
    {
        return new GearmanConnectionFactory();
    }
}
