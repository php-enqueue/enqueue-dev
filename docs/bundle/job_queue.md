---
layout: default
parent: "Symfony bundle"
title: Job queue
nav_order: 8
---
{% include support.md %}

# Jobs

Use jobs when your message flow has several steps(tasks) which run one after another.
Also jobs guaranty that job is unique i.e. you cant start new job with same name
until previous job has finished.

* [Installation](#installation)
* [Unique job](#unique-job)
* [Sub jobs](#sub-jobs)
* [Dependent Job](#dependent-job)

## Installation

The easiest way to install Enqueue's job queues is to by requiring a `enqueue/job-queue-pack` pack.
It installs installs everything you need. It also configures everything for you If you are on Symfony Flex.

```bash
$ composer require enqueue/job-queue-pack=^0.8
```

_**Note:** As long as you are on Symfony Flex you are done. If not, keep reading the installation chapter._

* Register installed bundles

```php
<?php
// app/AppKernel.php

use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Enqueue\Bundle\EnqueueBundle(),
        ];

        return $bundles;
    }
}
````

* Configure installed bundles:

```yaml
# app/config/config.yml

enqueue:
    default:
        # plus basic bundle configuration

        job: true
        
        # adds bundle's default Job entity mapping to application's entity manager.
        # set it to false when using your own mapped entities for jobs.
        default_mapping: true

doctrine:
    # plus basic bundle configuration

    orm:
        mappings:
            EnqueueJobQueue:
                is_bundle: false
                type: xml
                dir: '%kernel.project_dir%/vendor/enqueue/job-queue/Doctrine/mapping'
                prefix: 'Enqueue\JobQueue\Doctrine\Entity'

```

* Run doctrine schema update command

```bash
$ bin/console doctrine:schema:update
```

## Unique job

Guarantee that there is only one job with such name running at a time.
For example you have a task that builds a search index.
It takes quite a lot of time and you don't want another instance of same task working at the same time.
Here's how to do it:

* Write a job processor class:

```php
<?php
namespace App\Queue;

use Interop\Queue\Message;
use Interop\Queue\Processor;
use Interop\Queue\Context;
use Enqueue\Util\JSON;
use Enqueue\JobQueue\JobRunner;
use Enqueue\JobQueue\Job;
use Enqueue\Client\CommandSubscriberInterface;

class SearchReindexProcessor implements Processor, CommandSubscriberInterface
{
    private $jobRunner;

    public function __construct(JobRunner $jobRunner)
    {
        $this->jobRunner = $jobRunner;
    }

    public function process(Message $message, Context $context)
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

    public static function getSubscribedCommand()
    {
        return 'search_reindex';
    }
}
```

* Register it

```yaml
services:
  app_queue_search_reindex_processor:
    class: 'App\Queue\SearchReindexProcessor'
    arguments: ['@Enqueue\JobQueue\JobRunner']
    tags:
        - { name: 'enqueue.command_subscriber' }
```

* Schedule command

```php
<?php
use Symfony\Component\DependencyInjection\ContainerInterface;
use Enqueue\Client\ProducerInterface;

/** @var ContainerInterface $container  */

$producer = $container->get(ProducerInterface::class);

$producer->sendCommand('search_reindex');
```

## Sub jobs

Run several sub jobs in parallel. The steps are the same as we described above.

```php
<?php
use Enqueue\JobQueue\JobRunner;
use Enqueue\JobQueue\Job;
use Enqueue\Client\ProducerInterface;
use Enqueue\Util\JSON;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Interop\Queue\Processor;

class Step1Processor implements Processor
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var ProducerInterface
     */
    private $producer;

    public function process(Message $message, Context $context)
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
                            $this->producer->sendEvent('search:index:index-single-entity', [
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

class Step2Processor implements Processor
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    public function process(Message $message, Context $context)
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

## Dependent Job

Use dependent job when your job flow has several steps but you want to send new message
just after all steps are finished.
The steps are the same as we described above.

```php
<?php
use Enqueue\JobQueue\JobRunner;
use Enqueue\JobQueue\Job;
use Enqueue\JobQueue\DependentJobService;
use Enqueue\Util\JSON;
use Interop\Queue\Message;
use Interop\Queue\Context;
use Interop\Queue\Processor;

class ReindexProcessor implements Processor
{
    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var DependentJobService
     */
    private $dependentJob;

    public function process(Message $message, Context $context)
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

[back to index](index.md)
