<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\Context\InitLogger;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\ProcessorException;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\Exception\InvalidArgumentException;
use Enqueue\Consumption\Exception\LogicException;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Processor;
use Interop\Queue\Queue as InteropQueue;
use Interop\Queue\SubscriptionConsumer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class QueueConsumer implements QueueConsumerInterface
{
    /**
     * @var InteropContext
     */
    private $interopContext;

    /**
     * @var ExtensionInterface
     */
    private $staticExtension;

    /**
     * @var BoundProcessor[]
     */
    private $boundProcessors;

    /**
     * @var int in milliseconds
     */
    private $receiveTimeout;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SubscriptionConsumer
     */
    private $fallbackSubscriptionConsumer;

    /**
     * @param BoundProcessor[] $boundProcessors
     * @param int              $receiveTimeout  the time in milliseconds queue consumer waits for a message (10000 ms by default)
     */
    public function __construct(
        InteropContext $interopContext,
        ExtensionInterface $extension = null,
        array $boundProcessors = [],
        LoggerInterface $logger = null,
        int $receiveTimeout = 10000
    ) {
        $this->interopContext = $interopContext;
        $this->receiveTimeout = $receiveTimeout;

        $this->staticExtension = $extension ?: new ChainExtension([]);
        $this->logger = $logger ?: new NullLogger();

        $this->boundProcessors = [];
        array_walk($boundProcessors, function (BoundProcessor $processor) {
            $this->boundProcessors[] = $processor;
        });

        $this->fallbackSubscriptionConsumer = new FallbackSubscriptionConsumer();
    }

    public function setReceiveTimeout(int $timeout): void
    {
        $this->receiveTimeout = $timeout;
    }

    public function getReceiveTimeout(): int
    {
        return $this->receiveTimeout;
    }

    public function getContext(): InteropContext
    {
        return $this->interopContext;
    }

    public function bind($queue, Processor $processor): QueueConsumerInterface
    {
        if (is_string($queue)) {
            $queue = $this->interopContext->createQueue($queue);
        }

        InvalidArgumentException::assertInstanceOf($queue, InteropQueue::class);

        if (empty($queue->getQueueName())) {
            throw new LogicException('The queue name must be not empty.');
        }
        if (array_key_exists($queue->getQueueName(), $this->boundProcessors)) {
            throw new LogicException(sprintf('The queue was already bound. Queue: %s', $queue->getQueueName()));
        }

        $this->boundProcessors[$queue->getQueueName()] = new BoundProcessor($queue, $processor);

        return $this;
    }

    public function bindCallback($queue, callable $processor): QueueConsumerInterface
    {
        return $this->bind($queue, new CallbackProcessor($processor));
    }

    public function consume(ExtensionInterface $runtimeExtension = null): void
    {
        $extension = $runtimeExtension ?
            new ChainExtension([$this->staticExtension, $runtimeExtension]) :
            $this->staticExtension
        ;

        $initLogger = new InitLogger($this->logger);
        $extension->onInitLogger($initLogger);

        $this->logger = $initLogger->getLogger();

        $startTime = (int) (microtime(true) * 1000);

        $start = new Start(
            $this->interopContext,
            $this->logger,
            $this->boundProcessors,
            $this->receiveTimeout,
            $startTime
        );

        $extension->onStart($start);

        if ($start->isExecutionInterrupted()) {
            $this->onEnd($extension, $startTime, $start->getExitStatus());

            return;
        }

        $this->logger = $start->getLogger();
        $this->receiveTimeout = $start->getReceiveTimeout();
        $this->boundProcessors = $start->getBoundProcessors();

        if (empty($this->boundProcessors)) {
            throw new \LogicException('There is nothing to consume. It is required to bind something before calling consume method.');
        }

        /** @var Consumer[] $consumers */
        $consumers = [];
        foreach ($this->boundProcessors as $queueName => $boundProcessor) {
            $queue = $boundProcessor->getQueue();

            $consumers[$queue->getQueueName()] = $this->interopContext->createConsumer($queue);
        }

        try {
            $subscriptionConsumer = $this->interopContext->createSubscriptionConsumer();
        } catch (SubscriptionConsumerNotSupportedException $e) {
            $subscriptionConsumer = $this->fallbackSubscriptionConsumer;
        }

        $receivedMessagesCount = 0;
        $interruptExecution = false;

        $callback = function (InteropMessage $message, Consumer $consumer) use (&$receivedMessagesCount, &$interruptExecution, $extension) {
            ++$receivedMessagesCount;

            $receivedAt = (int) (microtime(true) * 1000);
            $queue = $consumer->getQueue();
            if (false == array_key_exists($queue->getQueueName(), $this->boundProcessors)) {
                throw new \LogicException(sprintf('The processor for the queue "%s" could not be found.', $queue->getQueueName()));
            }

            $processor = $this->boundProcessors[$queue->getQueueName()]->getProcessor();

            $messageReceived = new MessageReceived($this->interopContext, $consumer, $message, $processor, $receivedAt, $this->logger);
            $extension->onMessageReceived($messageReceived);
            $result = $messageReceived->getResult();
            $processor = $messageReceived->getProcessor();
            if (null === $result) {
                try {
                    $result = $processor->process($message, $this->interopContext);
                } catch (\Exception $e) {
                    $result = $this->onProcessorException($extension, $consumer, $message, $e, $receivedAt);
                }
            }

            $messageResult = new MessageResult($this->interopContext, $consumer, $message, $result, $receivedAt, $this->logger);
            $extension->onResult($messageResult);
            $result = $messageResult->getResult();

            switch ($result) {
                case Result::ACK:
                    $consumer->acknowledge($message);
                    break;
                case Result::REJECT:
                    $consumer->reject($message, false);
                    break;
                case Result::REQUEUE:
                    $consumer->reject($message, true);
                    break;
                case Result::ALREADY_ACKNOWLEDGED:
                    break;
                default:
                    throw new \LogicException(sprintf('Status is not supported: %s', $result));
            }

            $postMessageReceived = new PostMessageReceived($this->interopContext, $consumer, $message, $result, $receivedAt, $this->logger);
            $extension->onPostMessageReceived($postMessageReceived);

            if ($postMessageReceived->isExecutionInterrupted()) {
                $interruptExecution = true;

                return false;
            }

            return true;
        };

        foreach ($consumers as $queueName => $consumer) {
            /* @var Consumer $consumer */

            $preSubscribe = new PreSubscribe(
                $this->interopContext,
                $this->boundProcessors[$queueName]->getProcessor(),
                $consumer,
                $this->logger
            );

            $extension->onPreSubscribe($preSubscribe);

            $subscriptionConsumer->subscribe($consumer, $callback);
        }

        $cycle = 1;
        while (true) {
            $receivedMessagesCount = 0;
            $interruptExecution = false;

            $preConsume = new PreConsume($this->interopContext, $subscriptionConsumer, $this->logger, $cycle, $this->receiveTimeout, $startTime);
            $extension->onPreConsume($preConsume);

            if ($preConsume->isExecutionInterrupted()) {
                $this->onEnd($extension, $startTime, $preConsume->getExitStatus(), $subscriptionConsumer);

                return;
            }

            $subscriptionConsumer->consume($this->receiveTimeout);

            $postConsume = new PostConsume($this->interopContext, $subscriptionConsumer, $receivedMessagesCount, $cycle, $startTime, $this->logger);
            $extension->onPostConsume($postConsume);

            if ($interruptExecution || $postConsume->isExecutionInterrupted()) {
                $this->onEnd($extension, $startTime, $postConsume->getExitStatus(), $subscriptionConsumer);

                return;
            }

            ++$cycle;
        }
    }

    /**
     * @internal
     *
     * @param SubscriptionConsumer $fallbackSubscriptionConsumer
     */
    public function setFallbackSubscriptionConsumer(SubscriptionConsumer $fallbackSubscriptionConsumer): void
    {
        $this->fallbackSubscriptionConsumer = $fallbackSubscriptionConsumer;
    }

    private function onEnd(ExtensionInterface $extension, int $startTime, ?int $exitStatus = null, SubscriptionConsumer $subscriptionConsumer = null): void
    {
        $endTime = (int) (microtime(true) * 1000);

        $endContext = new End($this->interopContext, $startTime, $endTime, $this->logger, $exitStatus);
        $extension->onEnd($endContext);

        if ($subscriptionConsumer) {
            $subscriptionConsumer->unsubscribeAll();
        }
    }

    /**
     * The logic is similar to one in Symfony's ExceptionListener::onKernelException().
     *
     * https://github.com/symfony/symfony/blob/cbe289517470eeea27162fd2d523eb29c95f775f/src/Symfony/Component/HttpKernel/EventListener/ExceptionListener.php#L77
     */
    private function onProcessorException(ExtensionInterface $extension, Consumer $consumer, Message $message, \Exception $exception, int $receivedAt)
    {
        $processorException = new ProcessorException($this->interopContext, $consumer, $message, $exception, $receivedAt, $this->logger);

        try {
            $extension->onProcessorException($processorException);

            $result = $processorException->getResult();
            if (null === $result) {
                throw $exception;
            }

            return $result;
        } catch (\Exception $e) {
            $prev = $e;
            do {
                if ($exception === $wrapper = $prev) {
                    throw $e;
                }
            } while ($prev = $wrapper->getPrevious());

            $prev = new \ReflectionProperty($wrapper instanceof \Exception ? \Exception::class : \Error::class, 'previous');
            $prev->setAccessible(true);
            $prev->setValue($wrapper, $exception);

            throw $e;
        }
    }
}
