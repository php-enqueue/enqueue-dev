---
layout: default
parent: Job Queue
title: Run unique job
nav_order: 1
---
{% include support.md %}

## Job queue. Run unique job

There is job queue component build on top of a transport. It provides some additional features:

* Stores jobs to a database. So you can query that information and build a UI for it.
* Run unique job feature. If used guarantee that there is not any job with the same name running same time.
* Sub jobs. If used allow split a big job into smaller pieces and process them asynchronously and in parallel.
* Depended job. If used allow send a message when the whole job is finished (including sub jobs).

Here's some  examples.
It shows how you can run unique job using job queue (The configuration is described in a dedicated chapter).

```php
<?php
use Interop\Queue\Message;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use Enqueue\JobQueue\JobRunner;

class UniqueJobProcessor implements Processor
{
    /** @var JobRunner */
    private $jobRunner;

    public function process(Message $message, Context $context)
    {
        $result = $this->jobRunner->runUnique($message->getMessageId(), 'aJobName', function () {
            // do your job, there is no any other processes executing same job,

            return true; // if you want to ACK message or false to REJECT
        });

        return $result ? self::ACK : self::REJECT;
    }
}
```

[back to index](../index.md)
