<?php

namespace Enqueue\JobQueue;

use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Result;
use Enqueue\JobQueue\Doctrine\JobStorage;
use Enqueue\Util\JSON;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

class DependentJobProcessor implements PsrProcessor, TopicSubscriberInterface
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param JobStorage        $jobStorage
     * @param ProducerInterface $producer
     * @param LoggerInterface   $logger
     */
    public function __construct(JobStorage $jobStorage, ProducerInterface $producer, LoggerInterface $logger)
    {
        $this->jobStorage = $jobStorage;
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
            $this->logger->critical(sprintf(
                '[DependentJobProcessor] Got invalid message. body: "%s"',
                $message->getBody()
            ));

            return Result::REJECT;
        }

        $job = $this->jobStorage->findJobById($data['jobId']);
        if (!$job) {
            $this->logger->critical(sprintf(
                '[DependentJobProcessor] Job was not found. id: "%s"',
                $data['jobId']
            ));

            return Result::REJECT;
        }

        if (!$job->isRoot()) {
            $this->logger->critical(sprintf(
                '[DependentJobProcessor] Expected root job but got child. id: "%s"',
                $data['jobId']
            ));

            return Result::REJECT;
        }

        $jobData = $job->getData();

        if (!isset($jobData['dependentJobs'])) {
            return Result::ACK;
        }

        $dependentJobs = $jobData['dependentJobs'];

        foreach ($dependentJobs as $dependentJob) {
            if (!isset($dependentJob['topic']) || !isset($dependentJob['message'])) {
                $this->logger->critical(sprintf(
                    '[DependentJobProcessor] Got invalid dependent job data. job: "%s", dependentJob: "%s"',
                    $job->getId(),
                    JSON::encode($dependentJob)
                ));

                return Result::REJECT;
            }
        }

        foreach ($dependentJobs as $dependentJob) {
            $message = new Message();
            $message->setBody($dependentJob['message']);

            if (isset($dependentJob['priority'])) {
                $message->setPriority($dependentJob['priority']);
            }

            $this->producer->sendEvent($dependentJob['topic'], $message);
        }

        return Result::ACK;
    }

    public static function getSubscribedTopics()
    {
        return Topics::ROOT_JOB_STOPPED;
    }
}
