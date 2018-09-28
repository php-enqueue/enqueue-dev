<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanConnectionFactory;
use Interop\Queue\Spec\ContextSpec;

/**
 * @group functional
 */
class GearmanContextTest extends ContextSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return (new GearmanConnectionFactory(getenv('GEARMAN_DSN')))->createContext();
    }
}
