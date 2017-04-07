# Jobs

* [Unique job](#unique-job)
* [Sub jobs](#sub-jobs)


Use jobs when your message flow has several steps(tasks) which run one after another.
Also jobs guaranty that job is unique i.e. you cant start new job with same name
until previous job has finished.

## Unique job

Guaranty that there is only single job running with such name. 

```php
<?php 
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;
use Enqueue\Psr\PsrContext;
use Enqueue\Util\JSON;
use Enqueue\JobQueue\JobRunner;
use Enqueue\JobQueue\Job;

class ReindexProcessor implements PsrProcessor
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    public function process(PsrMessage $message, PsrContext $context)
    {
        $data = JSON::decode($message->getBody());

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            'search:index:reindex',
            function (JobRunner $runner, Job $job) use ($data) {
                // do your job

                return true; // if you want to ACK message or false to REJECT
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
}
```

## Sub jobs

Run several sub jobs in parallel. 

```php
<?php
use Enqueue\JobQueue\JobRunner;
use Enqueue\JobQueue\Job;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrProcessor;

class Step1Processor implements PsrProcessor 
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var ProducerInterface
     */
    private $producer;

    public function process(PsrMessage $message, PsrContext $context)
    {
        $data = JSON::decode($message->getBody());

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            'search:index:reindex',
            function (JobRunner $runner, Job $job) use ($data) {
                // for example first step generates tasks for step two

                foreach ($entities as $entity) {
                    // every job name must be unique
                    $jobName = 'search:index:index-single-entity:' . $entity->getId();
                    $runner->createDelayed(
                        $jobName,
                        function (JobRunner $runner, Job $childJob) use ($entity) {
                            $this->producer->send('search:index:index-single-entity', [
                                'entityId' => $entity->getId(),
                                'jobId' => $childJob->getId(),
                            ]);
                    });
                }

                return true; // if you want to ACK message or false to REJECT
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
}

class Step2Processor implements PsrProcessor 
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    public function process(PsrMessage $message, PsrContext $context)
    {
        $data = JSON::decode($message->getBody());

        $result = $this->jobRunner->runDelayed(
            $data['jobId'],
            function (JobRunner $runner, Job $job) use ($data) {
                // do your job

                return true; // if you want to ACK message or false to REJECT
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
}
```

###Dependent Job

Use dependent job when your job flow has several steps but you want to send new message
just after all steps are finished.

```php
<?php
use Enqueue\JobQueue\JobRunner;
use Enqueue\JobQueue\Job;
use Enqueue\JobQueue\DependentJobService;
use Enqueue\Util\JSON;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrProcessor;

class ReindexProcessor implements PsrProcessor 
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var DependentJobService
     */
    private $dependentJob;

    public function process(PsrMessage $message, PsrContext $context)
    {
        $data = JSON::decode($message->getBody());

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            'search:index:reindex',
            function (JobRunner $runner, Job $job) use ($data) {
                // register two dependent jobs
                // next messages will be sent to queue when that job and all children are finished
                $context = $this->dependentJob->createDependentJobContext($job->getRootJob());
                $context->addDependentJob('topic1', 'message1');
                $context->addDependentJob('topic2', 'message2');

                $this->dependentJob->saveDependentJob($context);

                // do your job

                return true; // if you want to ACK message or false to REJECT
            }
        );

        return $result ? self::ACK : self::REJECT;
    }
}
```

[back to index](../index.md)