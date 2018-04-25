<?php

namespace Enqueue\Redis\Tests\Functional;

use Enqueue\Redis\RedisContext;
use Enqueue\Test\RedisExtension;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class PhpRedisConsumptionUseCasesTest extends TestCase
{
    use RedisExtension;
    use ConsumptionUseCasesTrait;

    /**
     * @var RedisContext
     */
    private $context;

    public function setUp()
    {
        $this->context = $this->buildPhpRedisContext();

        $this->context->deleteQueue($this->context->createQueue('enqueue.test_queue'));
        $this->context->deleteQueue($this->context->createQueue('enqueue.test_queue_reply'));
    }

    public function tearDown()
    {
        $this->context->close();
    }

    /**
     * {@inheritdoc}
     */
    protected function getContext()
    {
        return $this->context;
    }
}
