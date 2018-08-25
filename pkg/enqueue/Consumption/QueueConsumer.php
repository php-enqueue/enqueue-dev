<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Exception\ConsumptionInterruptedException;
use Enqueue\Consumption\Exception\InvalidArgumentException;
use Enqueue\Consumption\Exception\LogicException;
use Enqueue\Util\VarExport;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrSubscriptionConsumer;
use Interop\Queue\SubscriptionConsumerNotSupportedException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class QueueConsumer implements QueueConsumerInterface
{
    /**
     * @var PsrContext
     */
    private $psrContext;

    /**
     * @var ExtensionInterface|ChainExtension
     */
    private $staticExtension;

    /**
     * [
     *   [PsrQueue, PsrProcessor],
     * ].
     *
     * @var array
     */
    private $boundProcessors;

    /**
     * @var int|float in milliseconds
     */
    private $idleTimeout;

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
     * @var PsrSubscriptionConsumer
     */
    private $fallbackSubscriptionConsumer;

    /**
     * @param PsrContext                             $psrContext
     * @param ExtensionInterface|ChainExtension|null $extension
     * @param int|float                              $idleTimeout    the time in milliseconds queue consumer waits if no message received
     * @param int|float                              $receiveTimeout the time in milliseconds queue consumer waits for a message (10 ms by default)
     */
    public function __construct(
        PsrContext $psrContext,
        ExtensionInterface $extension = null,
        float $idleTimeout = 0.,
        float $receiveTimeout = 10000.
    ) {
        $this->psrContext = $psrContext;
        $this->staticExtension = $extension ?: new ChainExtension([]);
        $this->idleTimeout = $idleTimeout;
        $this->receiveTimeout = $receiveTimeout;

        $this->boundProcessors = [];
        $this->logger = new NullLogger();
        $this->fallbackSubscriptionConsumer = new FallbackSubscriptionConsumer();
    }

    /**
     * {@inheritdoc}
     */
    public function setIdleTimeout(float $timeout): void
    {
        $this->idleTimeout = $timeout;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdleTimeout(): float
    {
        return $this->idleTimeout;
    }

    /**
     * {@inheritdoc}
     */
    public function setReceiveTimeout(float $timeout): void
    {
        $this->receiveTimeout = $timeout;
    }

    /**
     * {@inheritdoc}
     */
    public function getReceiveTimeout(): float
    {
        return $this->receiveTimeout;
    }

    /**
     * {@inheritdoc}
     */
    public function getPsrContext(): PsrContext
    {
        return $this->psrContext;
    }

    /**
     * {@inheritdoc}
     */
    public function bind($queue, PsrProcessor $processor): QueueConsumerInterface
    {
        if (is_string($queue)) {
            $queue = $this->psrContext->createQueue($queue);
        }

        InvalidArgumentException::assertInstanceOf($queue, PsrQueue::class);

        if (empty($queue->getQueueName())) {
            throw new LogicException('The queue name must be not empty.');
        }
        if (array_key_exists($queue->getQueueName(), $this->boundProcessors)) {
            throw new LogicException(sprintf('The queue was already bound. Queue: %s', $queue->getQueueName()));
        }

        $this->boundProcessors[$queue->getQueueName()] = [$queue, $processor];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bindCallback($queue, callable $processor): QueueConsumerInterface
    {
        return $this->bind($queue, new CallbackProcessor($processor));
    }

    /**
     * {@inheritdoc}
     */
    public function consume(ExtensionInterface $runtimeExtension = null): void
    {
        if (empty($this->boundProcessors)) {
            throw new \LogicException('There is nothing to consume. It is required to bind something before calling consume method.');
        }

        /** @var PsrConsumer[] $consumers */
        $consumers = [];
        /** @var PsrQueue $queue */
        foreach ($this->boundProcessors as list($queue, $processor)) {
            $consumers[$queue->getQueueName()] = $this->psrContext->createConsumer($queue);
        }

        $this->extension = $runtimeExtension ?
            new ChainExtension([$this->staticExtension, $runtimeExtension]) :
            $this->staticExtension
        ;

        $context = new Context($this->psrContext);
        $this->extension->onStart($context);

        if ($context->getLogger()) {
            $this->logger = $context->getLogger();
        } else {
            $this->logger = new NullLogger();
            $context->setLogger($this->logger);
        }

        $this->logger->info('Start consuming');

        try {
            $subscriptionConsumer = $this->psrContext->createSubscriptionConsumer();
        } catch (SubscriptionConsumerNotSupportedException $e) {
            $subscriptionConsumer = $this->fallbackSubscriptionConsumer;
        }

        $callback = function (PsrMessage $message, PsrConsumer $consumer) use (&$context) {
            $currentProcessor = null;

            /** @var PsrQueue $queue */
            foreach ($this->boundProcessors as list($queue, $processor)) {
                if ($queue->getQueueName() === $consumer->getQueue()->getQueueName()) {
                    $currentProcessor = $processor;
                }
            }

            if (false == $currentProcessor) {
                throw new \LogicException(sprintf('The processor for the queue "%s" could not be found.', $consumer->getQueue()->getQueueName()));
            }

            $context = new Context($this->psrContext);
            $context->setLogger($this->logger);
            $context->setPsrQueue($consumer->getQueue());
            $context->setPsrConsumer($consumer);
            $context->setPsrProcessor($currentProcessor);
            $context->setPsrMessage($message);

            $this->processMessage($consumer, $currentProcessor, $message, $context);

            if ($context->isExecutionInterrupted()) {
                throw new ConsumptionInterruptedException();
            }

            return true;
        };

        foreach ($consumers as $consumer) {
            /* @var PsrConsumer $consumer */

            $subscriptionConsumer->subscribe($consumer, $callback);
        }

        while (true) {
            try {
                $this->extension->onBeforeReceive($context);

                if ($context->isExecutionInterrupted()) {
                    throw new ConsumptionInterruptedException();
                }

                $subscriptionConsumer->consume($this->receiveTimeout);

                $this->idleTimeout && usleep($this->idleTimeout * 1000);
                $this->extension->onIdle($context);

                if ($context->isExecutionInterrupted()) {
                    throw new ConsumptionInterruptedException();
                }
            } catch (ConsumptionInterruptedException $e) {
                $this->logger->info(sprintf('Consuming interrupted'));

                foreach ($consumers as $consumer) {
                    /* @var PsrConsumer $consumer */

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
        }
    }

    /**
     * @internal
     *
     * @param PsrSubscriptionConsumer $fallbackSubscriptionConsumer
     */
    public function setFallbackSubscriptionConsumer(PsrSubscriptionConsumer $fallbackSubscriptionConsumer): void
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
        $this->logger = $context->getLogger();
        $this->logger->error(sprintf('Consuming interrupted by exception'));

        $exception = $context->getException();

        try {
            $this->extension->onInterrupted($context);
        } catch (\Exception $e) {
            // logic is similar to one in Symfony's ExceptionListener::onKernelException
            $this->logger->error(sprintf(
                'Exception thrown when handling an exception (%s: %s at %s line %s)',
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));

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

    private function processMessage(PsrConsumer $consumer, PsrProcessor $processor, PsrMessage $message, Context $context)
    {
        $this->logger->info('Message received from the queue: '.$context->getPsrQueue()->getQueueName());
        $this->logger->debug('Headers: {headers}', ['headers' => new VarExport($message->getHeaders())]);
        $this->logger->debug('Properties: {properties}', ['properties' => new VarExport($message->getProperties())]);
        $this->logger->debug('Payload: {payload}', ['payload' => new VarExport($message->getBody())]);

        $this->extension->onPreReceived($context);
        if (!$context->getResult()) {
            $result = $processor->process($message, $context->getPsrContext());
            $context->setResult($result);
        }

        $this->extension->onResult($context);

        switch ($context->getResult()) {
            case Result::ACK:
                $consumer->acknowledge($message);
                break;
            case Result::REJECT:
                $consumer->reject($message, false);
                break;
            case Result::REQUEUE:
                $consumer->reject($message, true);
                break;
            default:
                throw new \LogicException(sprintf('Status is not supported: %s', $context->getResult()));
        }

        $this->logger->info(sprintf('Message processed: %s', $context->getResult()));

        $this->extension->onPostReceived($context);
    }
}
