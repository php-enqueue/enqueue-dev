<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaContext;
use Interop\Queue\Spec\ContextSpec;

/**
 * @group rdkafka
 */
class RdKafkaContextTest extends ContextSpec
{
    protected function createContext()
    {
        return new RdKafkaContext([
            'global' => [
                'group.id' => 'group',
            ],
        ]);
    }
}
