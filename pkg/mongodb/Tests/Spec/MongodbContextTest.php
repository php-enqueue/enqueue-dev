<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Test\MongodbExtensionTrait;
use Interop\Queue\Spec\ContextSpec;

/**
 * @group functional
 * @group mongodb
 */
class MongodbContextTest extends ContextSpec
{
    use MongodbExtensionTrait;

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return $this->buildMongodbContext();
    }
}
