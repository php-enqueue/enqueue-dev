<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Interop\Queue\Spec\PsrContextSpec;

/**
 * @group functional
 */
class MongodbContextTest extends PsrContextSpec
{
    use CreateMongodbContextTrait;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->createMongodbContext();
    }
}
