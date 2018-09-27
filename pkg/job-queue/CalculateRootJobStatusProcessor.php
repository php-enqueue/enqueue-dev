<?php

namespace Enqueue\JobQueue;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Consumption\Result;
use Enqueue\JobQueue\Doctrine\JobStorage;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

class CalculateRootJobStatusProcessor implements PsrProcessor, CommandSubscriberInterface
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
            $this->producer->sendEvent(Topics::ROOT_JOB_STOPPED, [
                'jobId' => $job->getRootJob()->getId(),
            ]);
        }

        return Result::ACK;
    }

    public static function getSubscribedCommand()
    {
        return Commands::CALCULATE_ROOT_JOB_STATUS;
    }
}
