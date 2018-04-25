<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaContext;
use Interop\Queue\Spec\PsrContextSpec;

/**
 * @group rdkafka
 */
class RdKafkaContextTest extends PsrContextSpec
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
