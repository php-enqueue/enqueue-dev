<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Exception\ConsumptionInterruptedException;
use Enqueue\Consumption\Exception\InvalidArgumentException;
use Enqueue\Consumption\Exception\LogicException;
use Enqueue\Util\VarExport;
use Interop\Amqp\AmqpConsumer;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use Interop\Queue\PsrQueue;
use Psr\Log\NullLogger;

class QueueConsumer
{
    /**
     * @var PsrContext
     */
    private $psrContext;

    /**
     * @var ExtensionInterface|ChainExtension|null
     */
    private $extension;

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
     * @param PsrContext                             $psrContext
     * @param ExtensionInterface|ChainExtension|null $extension
     * @param int|float                              $idleTimeout    the time in milliseconds queue consumer waits if no message received
     * @param int|float                              $receiveTimeout the time in milliseconds queue consumer waits for a message (10 ms by default)
     */
    public function __construct(
        PsrContext $psrContext,
        ExtensionInterface $extension = null,
        $idleTimeout = 0,
        $receiveTimeout = 10
    ) {
        $this->psrContext = $psrContext;
        $this->extension = $extension;
        $this->idleTimeout = $idleTimeout;
        $this->receiveTimeout = $receiveTimeout;

        $this->boundProcessors = [];
    }

    /**
     * @param int $timeout
     */
    public function setIdleTimeout($timeout)
    {
        $this->idleTimeout = (int) $timeout;
    }

    /**
     * @return int
     */
    public function getIdleTimeout()
    {
        return $this->idleTimeout;
    }

    /**
     * @param int $timeout
     */
    public function setReceiveTimeout($timeout)
    {
        $this->receiveTimeout = (int) $timeout;
    }

    /**
     * @return int
     */
    public function getReceiveTimeout()
    {
        return $this->receiveTimeout;
    }

    /**
     * @return PsrContext
     */
    public function getPsrContext()
    {
        return $this->psrContext;
    }

    /**
     * @param PsrQueue|string       $queue
     * @param PsrProcessor|callable $processor
     *
     * @return QueueConsumer
     */
    public function bind($queue, $processor)
    {
        if (is_string($queue)) {
            $queue = $this->psrContext->createQueue($queue);
        }
        if (is_callable($processor)) {
            $processor = new CallbackProcessor($processor);
        }

        InvalidArgumentException::assertInstanceOf($queue, PsrQueue::class);
        InvalidArgumentException::assertInstanceOf($processor, PsrProcessor::class);

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
     * Runtime extension - is an extension or a collection of extensions which could be set on runtime.
     * Here's a good example: @see LimitsExtensionsCommandTrait.
     *
     * @param ExtensionInterface|ChainExtension|null $runtimeExtension
     *
     * @throws \Exception
     */
    public function consume(ExtensionInterface $runtimeExtension = null)
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

        $extension = $this->extension ?: new ChainExtension([]);
        if ($runtimeExtension) {
            $extension = new ChainExtension([$extension, $runtimeExtension]);
        }

        $context = new Context($this->psrContext);
        $extension->onStart($context);

        $logger = $context->getLogger() ?: new NullLogger();
        $logger->info('Start consuming');

        while (true) {
            try {
                if ($this->psrContext instanceof AmqpContext) {
                    $callback = function (AmqpMessage $message, AmqpConsumer $consumer) use ($extension, $logger, &$context) {
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
                        $context->setLogger($logger);
                        $context->setPsrQueue($consumer->getQueue());
                        $context->setPsrConsumer($consumer);
                        $context->setPsrProcessor($currentProcessor);
                        $context->setPsrMessage($message);

                        $this->doConsume($extension, $context);

                        return true;
                    };

                    foreach ($consumers as $consumer) {
                        /* @var AmqpConsumer $consumer */

                        $this->psrContext->subscribe($consumer, $callback);
                    }

                    $this->psrContext->consume($this->receiveTimeout);

                    usleep($this->idleTimeout * 1000);
                    $extension->onIdle($context);
                } else {
                    /** @var PsrQueue $queue */
                    foreach ($this->boundProcessors as list($queue, $processor)) {
                        $consumer = $consumers[$queue->getQueueName()];

                        $context = new Context($this->psrContext);
                        $context->setLogger($logger);
                        $context->setPsrQueue($queue);
                        $context->setPsrConsumer($consumer);
                        $context->setPsrProcessor($processor);

                        $this->doConsume($extension, $context);
                    }
                }
            } catch (ConsumptionInterruptedException $e) {
                $logger->info(sprintf('Consuming interrupted'));

                if ($this->psrContext instanceof AmqpContext) {
                    foreach ($consumers as $consumer) {
                        /* @var AmqpConsumer $consumer */

                        $this->psrContext->unsubscribe($consumer);
                    }
                }

                $context->setExecutionInterrupted(true);

                $extension->onInterrupted($context);

                return;
            } catch (\Exception $exception) {
                $context->setExecutionInterrupted(true);
                $context->setException($exception);

                try {
                    $this->onInterruptionByException($extension, $context);
                } catch (\Exception $e) {
                    // for some reason finally does not work here on php5.5

                    throw $e;
                }
            }
        }
    }

    /**
     * @param ExtensionInterface $extension
     * @param Context            $context
     *
     * @throws ConsumptionInterruptedException
     *
     * @return bool
     */
    protected function doConsume(ExtensionInterface $extension, Context $context)
    {
        $processor = $context->getPsrProcessor();
        $consumer = $context->getPsrConsumer();
        $logger = $context->getLogger();

        if (false == $context->getPsrMessage() instanceof AmqpContext) {
            $extension->onBeforeReceive($context);
        }

        if ($context->isExecutionInterrupted()) {
            throw new ConsumptionInterruptedException();
        }

        $message = $context->getPsrMessage();
        if (false == $message) {
            if ($message = $consumer->receive($this->receiveTimeout)) {
                $context->setPsrMessage($message);
            }
        }

        if ($message) {
            $logger->info('Message received from the queue: '.$context->getPsrQueue()->getQueueName());
            $logger->debug('Headers: {headers}', ['headers' => new VarExport($message->getHeaders())]);
            $logger->debug('Properties: {properties}', ['properties' => new VarExport($message->getProperties())]);
            $logger->debug('Payload: {payload}', ['payload' => new VarExport($message->getBody())]);

            $extension->onPreReceived($context);
            if (!$context->getResult()) {
                $result = $processor->process($message, $this->psrContext);
                $context->setResult($result);
            }

            $extension->onResult($context);

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

            $logger->info(sprintf('Message processed: %s', $context->getResult()));

            $extension->onPostReceived($context);

            if ($context->getPsrMessage() instanceof AmqpContext) {
                $extension->onBeforeReceive($context);
            }
        } else {
            usleep($this->idleTimeout * 1000);
            $extension->onIdle($context);
        }

        if ($context->isExecutionInterrupted()) {
            throw new ConsumptionInterruptedException();
        }
    }

    /**
     * @param ExtensionInterface $extension
     * @param Context            $context
     *
     * @throws \Exception
     */
    protected function onInterruptionByException(ExtensionInterface $extension, Context $context)
    {
        $logger = $context->getLogger();
        $logger->error(sprintf('Consuming interrupted by exception'));

        $exception = $context->getException();

        try {
            $extension->onInterrupted($context);
        } catch (\Exception $e) {
            // logic is similar to one in Symfony's ExceptionListener::onKernelException
            $logger->error(sprintf(
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
}
