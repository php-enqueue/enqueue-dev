<?php

namespace Enqueue\Redis\Tests\Functional;

use Enqueue\Redis\RedisContext;
use Enqueue\Test\RedisExtension;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 */
class PhpRedisCommonUseCasesTest extends TestCase
{
    use RedisExtension;
    use CommonUseCasesTrait;

    /**
     * @var RedisContext
     */
    private $context;

    public function setUp(): void
    {
        $this->context = $this->buildPhpRedisContext();

        $this->context->deleteQueue($this->context->createQueue('enqueue.test_queue'));
        $this->context->deleteTopic($this->context->createTopic('enqueue.test_topic'));
    }

    public function tearDown(): void
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
