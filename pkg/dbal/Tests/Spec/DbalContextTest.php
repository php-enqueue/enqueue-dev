<?php

namespace Enqueue\Dbal\Tests\Spec;

use Interop\Queue\Spec\ContextSpec;

/**
 * @group functional
 */
class DbalContextTest extends ContextSpec
{
    use CreateDbalContextTrait;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->createDbalContext();
    }
}
