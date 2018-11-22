<?php

namespace Enqueue\Wamp\Tests\Spec;

use Enqueue\Test\WampExtension;
use Interop\Queue\Spec\ContextSpec;

/**
 * @group functional
 * @group Wamp
 */
class WampContextTest extends ContextSpec
{
    use WampExtension;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->buildWampContext();
    }
}
