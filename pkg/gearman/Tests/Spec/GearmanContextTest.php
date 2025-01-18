<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanConnectionFactory;
use Interop\Queue\Spec\ContextSpec;

/**
 * @group functional
 * @group gearman
 */
class GearmanContextTest extends ContextSpec
{
    protected function createContext()
    {
        return (new GearmanConnectionFactory(getenv('GEARMAN_DSN')))->createContext();
    }
}
