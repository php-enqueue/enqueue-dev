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
    use ConsumptionUseCasesTrait;
    use RedisExtension;

    /**
     * @var RedisContext
     */
    private $context;

    protected function setUp(): void
    {
        $this->context = $this->buildPhpRedisContext();

        $this->context->deleteQueue($this->context->createQueue('enqueue.test_queue'));
        $this->context->deleteQueue($this->context->createQueue('enqueue.test_queue_reply'));
    }

    protected function tearDown(): void
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
