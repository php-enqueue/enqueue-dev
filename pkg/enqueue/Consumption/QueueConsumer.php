<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\ProcessorException;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\Exception\ConsumptionInterruptedException;
use Enqueue\Consumption\Exception\InvalidArgumentException;
use Enqueue\Consumption\Exception\LogicException;
use Enqueue\Util\VarExport;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
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
     * @var ExtensionInterface|ChainExtension
     */
    private $staticExtension;

    /**
     * @var BoundProcessor[]
     */
    private $boundProcessors;

    /**
     * @var int|float in milliseconds
     */
    private $receiveTimeout;

    /**
     * @var ExtensionInterface|ChainExtension
     */
    private $extension;

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
     * @param int|float        $receiveTimeout  the time in milliseconds queue consumer waits for a message (10 ms by default)
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
        /*
         * onStart             +
         * onPreSubscribe      +
         * onPreConsume        +
         * onMessageReceived   +
         * onResult
         * onProcessorException
         * onPostMessageReceived
         * onPostConsume
         * onEnd
         */

        $this->extension = $runtimeExtension ?
            new ChainExtension([$this->staticExtension, $runtimeExtension]) :
            $this->staticExtension
        ;

        $startTime = (int) (microtime(true) * 1000);

        $start = new Start(
            $this->interopContext,
            $this->logger,
            $this->boundProcessors,
            $this->receiveTimeout,
            $startTime
        );

        $this->extension->onStart($start);

        // todo
        if ($start->isExecutionInterrupted()) {
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

        // todo remove
        $context = new Context($this->interopContext);
        $context->setLogger($this->logger);
//        $this->extension->onStart($context);
//
//        if ($context->getLogger()) {
//            $this->logger = $context->getLogger();
//        } else {
//            $this->logger = new NullLogger();
//            $context->setLogger($this->logger);
//        }

//        $this->logger->info('Start consuming');

        try {
            $subscriptionConsumer = $this->interopContext->createSubscriptionConsumer();
        } catch (SubscriptionConsumerNotSupportedException $e) {
            $subscriptionConsumer = $this->fallbackSubscriptionConsumer;
        }

        $receivedMessagesCount = 0;

        $callback = function (InteropMessage $message, Consumer $consumer) use (&$context, &$receivedMessagesCount) {
            ++$receivedMessagesCount;

            $receivedAt = (int) (microtime(true) * 1000);
            $queue = $consumer->getQueue();
            if (false == array_key_exists($queue->getQueueName(), $this->boundProcessors)) {
                throw new \LogicException(sprintf('The processor for the queue "%s" could not be found.', $queue->getQueueName()));
            }

            $processor = $this->boundProcessors[$queue->getQueueName()]->getProcessor();

//            $this->logger->info('Message received from the queue: '.$context->getInteropQueue()->getQueueName());
//            $this->logger->debug('Headers: {headers}', ['headers' => new VarExport($message->getHeaders())]);
//            $this->logger->debug('Properties: {properties}', ['properties' => new VarExport($message->getProperties())]);
//            $this->logger->debug('Payload: {payload}', ['payload' => new VarExport($message->getBody())]);

            // TODO remove
            $context = new Context($this->interopContext);
            $context->setLogger($this->logger);
            $context->setInteropQueue($consumer->getQueue());
            $context->setConsumer($consumer);
            $context->setProcessor($processor);
            $context->setInteropMessage($message);

            $messageReceived = new MessageReceived($this->interopContext, $consumer, $message, $processor, $receivedAt, $this->logger);
            $this->extension->onMessageReceived($messageReceived);
            $result = $messageReceived->getResult();
            $processor = $messageReceived->getProcessor();
            if (null === $result) {
                try {
                    $result = $processor->process($message, $context->getInteropContext());

                    $context->setResult($result);
                } catch (\Exception $e) {
                    $processorException = new ProcessorException($this->interopContext, $message, $e, $receivedAt, $this->logger);
                    $this->extension->onProcessorException($processorException);

                    $result = $processorException->getResult();
                    if (null === $result) {
                        throw $e;
                    }
                }
            }

            $messageResult = new MessageResult($this->interopContext, $message, $result, $receivedAt, $this->logger);
            $this->extension->onResult($messageResult);
            $result = $messageResult->getResult();

            //todo
            $context->setResult($result);

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

//            $this->logger->info(sprintf('Message processed: %s', $result));

            $postMessageReceived = new PostMessageReceived($this->interopContext, $message, $result, $receivedAt, $this->logger);
            $this->extension->onPostMessageReceived($postMessageReceived);

            if ($postMessageReceived->isExecutionInterrupted()) {
                throw new ConsumptionInterruptedException();
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

            $this->extension->onPreSubscribe($preSubscribe);

            $subscriptionConsumer->subscribe($consumer, $callback);
        }

        $cycle = 1;
        while (true) {
            try {
                $receivedMessagesCount = 0;

                $preConsume = new PreConsume($this->interopContext, $subscriptionConsumer, $this->logger, $cycle, $this->receiveTimeout, $startTime);
                $this->extension->onPreConsume($preConsume);

                if ($preConsume->isExecutionInterrupted()) {
                    throw new ConsumptionInterruptedException();
                }

                $subscriptionConsumer->consume($this->receiveTimeout);

                $postConsume = new PostConsume($this->interopContext, $subscriptionConsumer, $receivedMessagesCount, $cycle, $startTime, $this->logger);
                $this->extension->onPostConsume($postConsume);

                if ($postConsume->isExecutionInterrupted()) {
                    throw new ConsumptionInterruptedException();
                }
            } catch (ConsumptionInterruptedException $e) {
                $this->logger->info(sprintf('Consuming interrupted'));

                foreach ($consumers as $consumer) {
                    /* @var Consumer $consumer */

                    $subscriptionConsumer->unsubscribe($consumer);
                }

                $context->setExecutionInterrupted(true);

                $this->extension->onInterrupted($context);

                return;
            } catch (\Throwable $exception) {
                $context->setExecutionInterrupted(true);
                $context->setException($exception);

                $this->onInterruptionByException($this->extension, $context);

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

    /**
     * @param ExtensionInterface $extension
     * @param Context            $context
     *
     * @throws \Exception
     */
    private function onInterruptionByException(ExtensionInterface $extension, Context $context)
    {
//        $this->logger = $context->getLogger();
//        $this->logger->error(sprintf('Consuming interrupted by exception'));

        $exception = $context->getException();

        try {
            $this->extension->onInterrupted($context);
        } catch (\Exception $e) {
            // logic is similar to one in Symfony's ExceptionListener::onKernelException
//            $this->logger->error(sprintf(
//                'Exception thrown when handling an exception (%s: %s at %s line %s)',
//                get_class($e),
//                $e->getMessage(),
//                $e->getFile(),
//                $e->getLine()
//            ));

            $wrapper = $e;
            while ($prev = $wrapper->getPrevious()) {
                if ($exception === $wrapper = $prev) {
                    throw $e;
                }
            }

            $prev = new \ReflectionProperty('Exception', 'previous');
            $prev->setAccessible(true);
            $prev->setValue($wrapper, $exception);

            throw $e;
        }

        throw $exception;
    }
}
