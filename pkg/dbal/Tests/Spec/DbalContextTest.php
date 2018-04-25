<?php

namespace Enqueue\Dbal\Tests\Spec;

use Interop\Queue\Spec\PsrContextSpec;

/**
 * @group functional
 */
class DbalContextTest extends PsrContextSpec
{
    use CreateMongodbContextTrait;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->createDbalContext();
    }
}
