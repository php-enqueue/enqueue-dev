<?php

namespace Enqueue\JobQueue;

use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Util\JSON;
use Psr\Log\LoggerInterface;

class CalculateRootJobStatusProcessor implements PsrProcessor, TopicSubscriberInterface
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var CalculateRootJobStatusService
     */
    private $calculateRootJobStatusService;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param JobStorage                    $jobStorage
     * @param CalculateRootJobStatusService $calculateRootJobStatusCase
     * @param ProducerInterface      $producer
     * @param LoggerInterface               $logger
     */
    public function __construct(
        JobStorage $jobStorage,
        CalculateRootJobStatusService $calculateRootJobStatusCase,
        ProducerInterface $producer,
        LoggerInterface $logger
    ) {
        $this->jobStorage = $jobStorage;
        $this->calculateRootJobStatusService = $calculateRootJobStatusCase;
        $this->producer = $producer;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $message, PsrContext $context)
    {
        $data = JSON::decode($message->getBody());

        if (!isset($data['jobId'])) {
            $this->logger->critical(sprintf('Got invalid message. body: "%s"', $message->getBody()));

            return Result::REJECT;
        }

        $job = $this->jobStorage->findJobById($data['jobId']);
        if (!$job) {
            $this->logger->critical(sprintf('Job was not found. id: "%s"', $data['jobId']));

            return Result::REJECT;
        }

        $isRootJobStopped = $this->calculateRootJobStatusService->calculate($job);

        if ($isRootJobStopped) {
            $this->producer->send(Topics::ROOT_JOB_STOPPED, [
                'jobId' => $job->getRootJob()->getId(),
            ]);
        }

        return Result::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CALCULATE_ROOT_JOB_STATUS];
    }
}
