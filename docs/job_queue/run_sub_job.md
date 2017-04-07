## Job queue. Run sub job

It shows how you can create and run a sub job, which it is executed separately. 
You can create as many sub jobs as you like. 
They will be executed in parallel. 

```php
<?php
use Enqueue\Client\ProducerInterface;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrProcessor;
use Enqueue\JobQueue\JobRunner;
use Enqueue\JobQueue\Job;
use Enqueue\Util\JSON;

class RootJobProcessor implements PsrProcessor
{
    /** @var JobRunner */
    private $jobRunner;
    
    /** @var  ProducerInterface */
    private $producer;

    public function process(PsrMessage $message, PsrContext $context)
    {
        $result = $this->jobRunner->runUnique($message->getMessageId(), 'aJobName', function (JobRunner $runner) {
            $runner->createDelayed('aSubJobName1', function (JobRunner $runner, Job $childJob) {
                $this->producer->send('aJobTopic', [
                    'jobId' => $childJob->getId(),
                    // other data required by sub job
                ]);
            });

            return true;
        });

        return $result ? self::ACK : self::REJECT;
    }
}

class SubJobProcessor implements PsrProcessor
{
    /** @var JobRunner */
    private $jobRunner;

    public function process(PsrMessage $message, PsrContext $context)
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