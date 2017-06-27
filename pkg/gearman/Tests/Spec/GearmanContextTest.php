<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanConnectionFactory;
use Enqueue\Psr\Spec\PsrContextSpec;

/**
 * @group functional
 */
class GearmanContextTest extends PsrContextSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return (new GearmanConnectionFactory(getenv('GEARMAN_DSN')))->createContext();
    }
}
