---
layout: default
parent: Job Queue
title: Run sub job
nav_order: 2
---
{% include support.md %}

## Job queue. Run sub job

It shows how you can create and run a sub job, which it is executed separately.
You can create as many sub jobs as you like.
They will be executed in parallel.

```php
<?php
use Enqueue\Client\ProducerInterface;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use Enqueue\JobQueue\JobRunner;
use Enqueue\JobQueue\Job;
use Enqueue\Util\JSON;

class RootJobProcessor implements Processor
{
    /** @var JobRunner */
    private $jobRunner;

    /** @var  ProducerInterface */
    private $producer;

    public function process(Message $message, Context $context)
    {
        $result = $this->jobRunner->runUnique($message->getMessageId(), 'aJobName', function (JobRunner $runner) {
            $runner->createDelayed('aSubJobName1', function (JobRunner $runner, Job $childJob) {
                $this->producer->sendEvent('aJobTopic', [
                    'jobId' => $childJob->getId(),
                    // other data required by sub job
                ]);
            });

            return true;
        });

        return $result ? self::ACK : self::REJECT;
    }
}

class SubJobProcessor implements Processor
{
    /** @var JobRunner */
    private $jobRunner;

    public function process(Message $message, Context $context)
    {
        $data = JSON::decode($message->getBody());

        $result = $this->jobRunner->runDelayed($data['jobId'], function () use ($data) {
            // do your job

            return true;
        });

        return $result ? self::ACK : self::REJECT;
    }
}
```

[back to index](../index.md)
