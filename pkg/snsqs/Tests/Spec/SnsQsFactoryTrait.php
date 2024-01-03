<?php

namespace Enqueue\SnsQs\Tests\Spec;

use Enqueue\SnsQs\SnsQsContext;
use Enqueue\SnsQs\SnsQsQueue;
use Enqueue\SnsQs\SnsQsTopic;
use Enqueue\Test\SnsQsExtension;

trait SnsQsFactoryTrait
{
    use SnsQsExtension;

    /**
     * @var SnsQsContext
     */
    private $snsQsContext;

    /**
     * @var SnsQsTopic
     */
    private $snsQsTopic;

    /**
     * @var SnsQsQueue
     */
    private $snsQsQueue;

    protected function createSnsQsContext(): SnsQsContext
    {
        return $this->snsQsContext = $this->buildSnsQsContext();
    }

    protected function createSnsQsQueue(string $queueName): SnsQsQueue
    {
        $queueName = $queueName.time();

        $this->snsQsQueue = $this->snsQsContext->createQueue($queueName);
        $this->snsQsContext->declareQueue($this->snsQsQueue);
        echo "Declared queue $queueName\n";
        ob_flush();
        sleep(1);

        if ($this->snsQsTopic) {
            $this->snsQsContext->bind($this->snsQsTopic, $this->snsQsQueue);
            echo "Bound queue $queueName to topic\n";
            ob_flush();
        }

        return $this->snsQsQueue;
    }

    protected function createSnsQsTopic(string $topicName): SnsQsTopic
    {
        $topicName = $topicName.time();

        $this->snsQsTopic = $this->snsQsContext->createTopic($topicName);
        $this->snsQsContext->declareTopic($this->snsQsTopic);

        return $this->snsQsTopic;
    }

    protected function cleanUpSnsQs(): void
    {
        if ($this->snsQsTopic) {
            $this->snsQsContext->deleteTopic($this->snsQsTopic);
        }

        if ($this->snsQsQueue) {
            $this->snsQsContext->deleteQueue($this->snsQsQueue);
        }
    }
}
